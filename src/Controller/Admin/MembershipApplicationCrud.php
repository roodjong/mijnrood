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
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
// loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
$dotenv->loadEnv('/home/u8184p5640/domains/socialistenrotterdam.nl/ledenadmin/.env.local');
$mailadres=$_ENV['AFDELINGSMAIL'];
$orgnaam=$_ENV['ORGNAAM'];
class MembershipApplicationCrud extends AbstractCrudController
{
    private $crudUrlGenerator;

    public function __construct(MailerInterface $mailer, AdminUrlGenerator $crudUrlGenerator) {
        $this->mailer = $mailer;
        $this->crudUrlGenerator = $crudUrlGenerator;
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
        $noreply=$_ENV['NOREPLY'];
        $orgnaam=$_ENV['ORGNAAM'];
        $mailer = $this->mailer;

        $application = $context->getEntity()->getInstance();
        $member = $application->createMember();
        $member->setNewPasswordToken(sha1($member->getEmail().time()));

        $em = $this->getDoctrine()->getManager();
        $em->persist($member);
        $em->remove($application);
        $em->flush();

        $message = (new Email())
            ->subject('Welkom bij de vereniging!')
            ->to(new Address($member->getEmail(), $member->getFirstName() .' '. $member->getLastName()))
            ->from(new Address($noreply,$orgnaam))
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
                    ->from(new Address($noreply, $orgnaam))
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

        $url = $this->crudUrlGenerator
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
                ->hideOnIndex()
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division'))
        ;
    }

}
