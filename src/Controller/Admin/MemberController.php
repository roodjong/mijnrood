<?php

namespace App\Controller\Admin;

use App\Entity\Member;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, EmailField, AssociationField };
use App\Form\ContributionPaymentType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class MemberController extends AbstractCrudController
{
    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return Member::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('lid')
            ->setEntityLabelInPlural('Leden')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'Lidnummer'),

            TextField::new('firstName', 'Voornaam'),
            TextField::new('lastName', 'Achternaam'),
            DateField::new('registrationTime', 'Inschrijfdatum')
                ->setFormat(DateTimeField::FORMAT_SHORT),
            AssociationField::new('division', 'Groep'),

            FormField::addPanel('Contactinfomratie'),
            EmailField::new('email', 'E-mailadres'),
            TextField::new('phone', 'Telefoonnummer'),
            TextField::new('address', 'Adres')->hideOnIndex(),
            TextField::new('city', 'Plaats'),
            TextField::new('postCode', 'Postcode')->hideOnIndex(),

            FormField::addPanel('Contributie'),
            TextField::new('iban', 'IBAN-rekeningnummer')->hideOnIndex(),
            ChoiceField::new('contributionPeriod', 'Betalingsperiode')
                ->setChoices([
                    'Maandelijks' => Member::PERIOD_MONTHLY,
                    'Kwartaallijks' => Member::PERIOD_QUARTERLY,
                    'Jaarlijks' => Member::PERIOD_QUARTERLY
                ]),
            CollectionField::new('contributionPayments', 'Betalingen')
                ->setEntryIsComplex(false)
                ->setEntryType(ContributionPaymentType::class)
                ->setFormTypeOptions([
                    'block_prefix' => 'collection_table',
                    'allow_add' => true,
                    'allow_delete' => false
                ])
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
