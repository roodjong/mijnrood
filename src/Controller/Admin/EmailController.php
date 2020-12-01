<?php

namespace App\Controller\Admin;

use App\Entity\Email;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, FormField, TextField, UrlField, EmailField, AssociationField };

class EmailController extends AbstractCrudController
{
    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return Email::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('user', 'Gebruikersdeel'),
            TextField::new('domain', 'Domein'),
        ];
    }

}
