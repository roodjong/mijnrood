<?php

namespace App\Form;

use App\Entity\MembershipApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Contribution\ContributionPeriodType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use App\Validator\Age;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;

class MembershipApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', null, ['label' => 'Voornaam', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('lastName', null, ['label' => 'Achternaam', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('email', null, ['label' => 'E-mailadres', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('phone', null, ['label' => 'Telefoonnummer', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('dateOfBirth', null, [
                'label' => 'Geboortedatum',
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [new NotBlank(), new Age(['min' => 14, 'max' => 27, 'message' => 'Je moet tussen de {{ min }} en {{ max }} jaar oud zijn om lid te worden van ROOD.'])],
                'error_bubbling' => true
            ])
            // ->add('iban', null, ['label' => 'IBAN-rekeningnummer', 'error_bubbling' => true])
            ->add('address', null, ['label' => 'Adres', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('city', null, ['label' => 'Plaats', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('postCode', null, ['label' => 'Postcode', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('preferredDivision', null, [
                'label' => 'Bij welke groep wil je je aansluiten',
                'query_builder' => function($repo) {
                    return $repo->createQueryBuilder('d')
                        ->where('d.canBeSelectedOnApplication = true')
                    ;
                },
                // 'placeholder' => 'Geen voorkeur'
            ])
            ->add('accept', CheckboxType::class, [
                'label' => 'Ik heb het <a target="_blank" href="https://roodjongindesp.nl/privacybeleid">privacybeleid</a> gelezen en ik ga daarmee akkoord.',
                'label_html' => true,
                'mapped' => false,
                'required' => true,
                'error_bubbling' => true,
                'constraints' => [new IsTrue(['message' => 'Je moet akkoord gaan met het privacybeleid van ROOD, tenzij je jonger bent dan 16.'])]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MembershipApplication::class,
        ]);
    }
}
