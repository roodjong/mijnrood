<?php

namespace App\Controller\Admin;

use App\Entity\{ Member, MembershipApplication };
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, BooleanField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField, MoneyField };
use App\Form\Admin\ContributionPaymentType;
use EasyCorp\Bundle\EasyAdminBundle\Config\{ Crud, Filters, Action, Actions };
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use Swift_Mailer, Swift_Message;

class MembershipApplicationCrud extends AbstractCrudController
{
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

    public function configureActions(Actions $actions): Actions {
        $action = Action::new('Afkeuren', 'Goedkeuren', 'fa fa-check')
            ->linkToCrudAction('acceptApplication');

        return $actions
            ->add(Crud::PAGE_DETAIL, $action)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT);
    }

    public function acceptApplication(AdminContext $context, Swift_Mailer $mailer) {
        $application = $context->getEntity()->getInstance();
        $member = $application->createMember();

        $em = $this->getDoctrine()->getManager();
        $em->persist($member);
        $em->remove($application);
        $em->flush();

        $message = (new Swift_Message())
            ->setSubject('Welkom bij ROOD, jong in de SP')
            ->setTo([$member->getEmail() => $member->getFirstName() .' '. $member->getLastName()])
            ->setFrom(['noreply@roodjongindesp.nl' => 'Mijn ROOD'])
            ->setBody(
                $this->renderView('email/html/welcome.html.twig', ['member' => $member]),
                'text/html'
            )
            ->addPart(
                $this->renderView('email/text/welcome.txt.twig', ['member' => $member]),
                'text/plain'
            );
        $mailer->send($message);

        $url = $this->get(CrudUrlGenerator::class)
            ->build()
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
