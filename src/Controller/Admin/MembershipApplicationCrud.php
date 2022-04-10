<?php

namespace App\Controller\Admin;

use App\Controller\Admin\MemberCrud;
use App\Entity\{ Member, MembershipApplication };
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use App\Form\Admin\ContributionPaymentType;
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Action, Actions };
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mollie\Api\MollieApiClient;

use DateTime;
use DateInterval;

class MembershipApplicationCrud extends AbstractCrudController
{
    private MailerInterface $mailer;
    private MollieApiClient $mollieApiClient;

    public function __construct(MailerInterface $mailer, MollieApiClient $mollieApiClient)
    {
        $this->mailer = $mailer;
        $this->mollieApiClient = $mollieApiClient;
    }

    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return MembershipApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('aanmelding')
            ->setEntityLabelInPlural('Lidmaatschapsaanmeldingen')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone', 'city', 'postCode'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $action = Action::new('accept', 'Goedkeuren', 'fa fa-check')
            ->linkToCrudAction('acceptApplication');

        return $actions
            ->add(Crud::PAGE_DETAIL, $action)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT);
    }

    public function acceptApplication(AdminContext $context)
    {
        $noreply = $this->getParameter('app.noReplyAddress');
        $organizationName = $this->getParameter('app.organizationName');
        $mailer = $this->mailer;

        $application = $context->getEntity()->getInstance();

        $mollieIntervals = [
            Member::PERIOD_MONTHLY => '1 month',
            Member::PERIOD_QUARTERLY => '3 months',
            Member::PERIOD_ANNUALLY => '1 year'
        ];
        $dateTimeIntervals = [
            Member::PERIOD_MONTHLY => 'P1M',
            Member::PERIOD_QUARTERLY => 'P3M',
            Member::PERIOD_ANNUALLY => 'P1Y'
        ];

        $startDate = new DateTime();
        $startDate->setDate(date('Y'), floor(date('m') / 3) + 1, 1);
        $startDate->add(new DateInterval($dateTimeIntervals[$application->getContributionPeriod()]));

        $customer = $this->mollieApiClient->customers->get($application->getMollieCustomerId());

        $subscription = $customer->createSubscription([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($application->getContributionPerPeriodInEuros(), 2, '.', '')
            ],
            'interval' => $mollieIntervals[$application->getContributionPeriod()],
            'description' => $this->getParameter('mollie_payment_description'),
            'startDate' => $startDate->format('Y-m-d'),
            'webhookUrl' => $this->generateUrl('member_contribution_mollie_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);

        $member = $application->createMember($subscription->id);
        $member->setNewPasswordToken(sha1($member->getEmail().time()));

        $em = $this->getDoctrine()->getManager();
        $em->persist($member);
        $em->remove($application);
        $em->flush();

        $message = (new Email())
            ->subject("Welkom bij $organizationName!")
            ->to(new Address($member->getEmail(), $member->getFirstName() .' '. $member->getLastName()))
            ->from(new Address($noreply,$organizationName))
            ->html(
                $this->renderView('email/html/welcome.html.twig', ['member' => $member])
            )
            ->text(
                $this->renderView('email/text/welcome.txt.twig', ['member' => $member])
            );
        $mailer->send($message);

        // Email naar de contactpersoon
        if ($member->getDivision() !== null)
        {
            if ($member->getDivision()->getContact() !== null)
            {
                $message2 = (new Email())
                    ->subject('Nieuw lid aangesloten bij je groep')
                    ->to(new Address($member->getDivision()->getContact()->getEmail(), $member->getDivision()->getContact()->getFirstName() .' '. $member->getDivision()->getContact()->getLastName()))
                    ->from(new Address($noreply, $organizationName))
                    ->html(
                        $this->renderView('email/html/contact_new_member.html.twig', [
                            'member' => $member,
                        ]),
                    )
                    ->text(
                        $this->renderView('email/text/contact_new_member.txt.twig', [
                            'member' => $member,
                        ]),
                    );
                $mailer->send($message2);
            }
        }

        $url = $this->conatiner->get(AdminUrlGenerator::class)
            ->setController(MemberCrud::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($member->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('firstName', 'Voornaam'),
            TextField::new('lastName', 'Achternaam'),
            DateField::new('dateOfBirth', 'Geboortedatum')
                ->hideOnIndex(),
            DateField::new('registrationTime', 'Inschrijfdatum')
                ->setFormat(DateTimeField::FORMAT_SHORT)
                ->hideOnIndex(),

            FormField::addPanel('Contactinformatie'),
            EmailField::new('email', 'E-mailadres'),
            TextField::new('phone', 'Telefoonnummer'),
            TextField::new('address', 'Adres')->hideOnIndex(),
            TextField::new('city', 'Plaats'),
            TextField::new('postCode', 'Postcode')->hideOnIndex(),
            TextField::new('country', 'Landcode')
                ->hideOnIndex()
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Twee-letterige landcode']]),
            AssociationField::new('preferredDivision', 'Gewenste groep')
                ->hideOnIndex(),

            FormField::addPanel('Contributie'),
            TextField::new('iban', 'IBAN-rekeningnummer')->hideOnIndex(),
            ChoiceField::new('contributionPeriod', 'Betalingsperiode')
                ->setChoices([
                    'Maandelijks' => Member::PERIOD_MONTHLY,
                    'Kwartaallijks' => Member::PERIOD_QUARTERLY,
                    'Jaarlijks' => Member::PERIOD_QUARTERLY
                ])
                ->hideOnIndex(),
            MoneyField::new('contributionPerPeriodInCents', 'Bedrag')
                ->setCurrency('EUR')
                ->hideOnIndex(),

            BooleanField::new('paid', 'Eerste contributie betaald')
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division'))
        ;
    }

}
