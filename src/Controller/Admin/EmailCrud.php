<?php

namespace App\Controller\Admin;

use App\Entity\Email;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{ IdField, FormField, TextField, PasswordField, AssociationField };
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class EmailCrud extends AbstractCrudController
{
    // it must return a FQCN (fully-qualified class name) of a Doctrine ORM entity
    public static function getEntityFqcn(): string
    {
        return Email::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('e-mailadres')
            ->setEntityLabelInPlural('E-mailadressen')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('user', 'Gebruikersdeel'),
            AssociationField::new('domain', 'Domein')
                ->setRequired(true),
            TextField::new('password', 'Wachtwoord')
                ->setRequired(true)
                ->setVirtual(true)
                ->onlyWhenCreating()
                ->setFormType(PasswordType::class),
            TextField::new('changePassword', 'Wachtwoord wijzigen')
                ->hideOnIndex()
                ->setVirtual(true)
                ->onlyWhenUpdating()
                ->setFormType(PasswordType::class)
        ];
    }

}
