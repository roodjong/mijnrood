<?php

namespace App\Controller\Admin;

use App\Entity\WorkGroup;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, FormField, BooleanField, TextField, UrlField, EmailField, AssociationField };
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class WorkGroupCrud extends AbstractCrudController
{
    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return WorkGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('werkgroep')
            ->setEntityLabelInPlural('Werkgroepen')
            ->setEntityPermission('ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Werkgroepnaam'),
            AssociationField::new('contact', 'Contactpersoon'),
            AssociationField::new('members', 'Leden')->hideOnIndex(),
            FormField::addPanel('Contactinformatie'),
            AssociationField::new('email', 'E-mailadres'),
        ];
    }

}
