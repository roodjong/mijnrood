<?php

namespace App\Controller\Admin;

use Doctrine\ORM\QueryBuilder;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use App\Entity\{ Member, MembershipApplication };
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Action, Actions };
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Exceptions\ApiException;

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

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $response = $this->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            $response->andWhere('entity.preferredDivision IN (:division)')->setParameter('division', $this->getUser()->getManagedDivisions());
        }

        return $response;
    }

    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return MembershipApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('aanmelding')
            ->setEntityLabelInPlural('Lidmaatschapsaanmeldingen')
            ->setSearchFields(['id', 'firstName', 'lastName', 'email', 'phone', 'city', 'postCode'])
        ;

        if ($this->getParameter('app.enableDivisionContactsCanApproveNewMembers')) {
            $crud->setEntityPermission('ROLE_DIVISION_CONTACT');
        } else {
            $crud->setEntityPermission('ROLE_ADMIN');
        }

        return $crud;
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
        $startDate->setDate(date('Y'), floor(date('m') / 3) * 3, 1);
        $startDate->add(new DateInterval($dateTimeIntervals[$application->getContributionPeriod()]));
        $subscriptionId = null;

        try {
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
            $subscriptionId = $subscription->id;

        } catch (ApiException $e) {
            // De subscription moet later nog gedaan worden door het lid zelf
            $this->addFlash("warning", "Het net geaccepteerde lid heeft nog geen automatisch incasso. Het nieuwe lid kan dit alleen zelf instellen.");
        }

        $member = $application->createMember($subscriptionId);
        $member->generateNewPasswordToken();

        $em = $this->getDoctrine()->getManager();
        $em->persist($member);
        $em->remove($application);
        $em->flush();

        $templatePrefix = '';

        if (is_dir($this->getParameter('kernel.project_dir') . '/templates/custom')) {
            $templatePrefix = 'custom/';
        }

        $message = (new Email())
            ->subject("Welkom bij $organizationName!")
            ->to(new Address($member->getEmail(), $member->getFullName()))
            ->from(new Address($noreply,$organizationName))
            ->html(
                $this->renderView($templatePrefix . 'email/html/welcome.html.twig', ['member' => $member])
            )
            ->text(
                $this->renderView($templatePrefix . 'email/text/welcome.txt.twig', ['member' => $member])
            );
        $mailer->send($message);

        // Email naar de contactpersoon
        if ($member->getDivision() !== null)
        {
            foreach ($member->getDivision()->getContacts() as $contact)
            {
                $message2 = (new Email())
                    ->subject('Nieuw lid aangesloten bij je groep')
                    ->to(new Address($contact->getEmail(), $contact->getFullName()))
                    ->from(new Address($noreply, $organizationName))
                    ->html(
                        $this->renderView($templatePrefix . 'email/html/contact_new_member.html.twig', [
                            'contact' => $contact,
                            'member' => $member,
                        ]),
                    )
                    ->text(
                        $this->renderView($templatePrefix . 'email/text/contact_new_member.txt.twig', [
                            'contact' => $contact,
                            'member' => $member,
                        ]),
                    );
                $mailer->send($message2);
            }
        }

        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(MemberCrud::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($member->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('firstName', 'Voornaam'),
        ];

        if ($this->getParameter('app.useMiddleName')) {
            $fields[] = TextField::new('middleName', 'Tussenvoegsel')->setRequired(false);
        }

        array_push($fields,
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
        );

        return $fields;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division'))
        ;
    }

}
