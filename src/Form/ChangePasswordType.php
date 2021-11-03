<?php

namespace App\Form;

use App\Entity\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ PasswordType, RepeatedType };

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Huidig wachtwoord'
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nieuw wachtwoord'
                ],
                'second_options' => [
                    'label' => 'Nieuw wachtwoord (nog een keer)'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }
}
