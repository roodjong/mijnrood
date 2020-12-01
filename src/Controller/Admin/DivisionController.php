<?php

namespace App\Controller\Admin;

use App\Entity\Division;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, FormField, TextField, UrlField, EmailField, AssociationField };
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class DivisionController extends AbstractCrudController
{
    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return Division::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('groep')
            ->setEntityLabelInPlural('Groepen')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Groepsnaam'),
            AssociationField::new('contact', 'Contactpersoon'),

            FormField::addPanel('Contactinformatie'),
            EmailField::new('email', 'E-mailadres'),
            TextField::new('phone', 'Telefoonnummer'),
            TextField::new('address', 'Postadres')->hideOnIndex(),
            TextField::new('city', 'Plaats postadres')->hideOnIndex(),
            TextField::new('postCode', 'Postcode')->hideOnIndex(),

            FormField::addPanel('Socialemedia-accounts'),
            UrlField::new('facebook', 'Facebook-URL')->hideOnIndex(),
            UrlField::new('twitter', 'Twitter-URL')->hideOnIndex(),
            UrlField::new('instagram', 'Instagram-URL')->hideOnIndex(),
        ];
    }

}
