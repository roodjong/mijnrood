<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, FormField, DateField, DateTimeField, CollectionField, ChoiceField, TextField, TextEditorField, EmailField, AssociationField, MoneyField };
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class EventCrud extends AbstractCrudController
{
    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('evenement')
            ->setEntityLabelInPlural('Evenementen')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),

            TextField::new('name', 'Naam'),
            TextEditorField::new('description', 'Omschrijving'),
            DateTimeField::new('timeStart', 'Aanvang')->renderAsChoice(),
            DateTimeField::new('timeEnd', 'Afloop')->renderAsChoice(),
            AssociationField::new('division', 'Afdeling')
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('division'))
        ;
    }

}
