<?php

namespace App\Form;

use App\Entity\MembershipApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Contribution\ContributionIncomeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Validator\Age;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class MembershipApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', null, ['label' => 'Voornaam', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('middleName', null, ['label' => 'Tussenvoegsel', 'error_bubbling' => true])
            ->add('lastName', null, ['label' => 'Achternaam', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('email', null, ['label' => 'E-mailadres', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('phone', null, ['label' => 'Telefoonnummer', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('dateOfBirth', null, [
                'label' => 'Geboortedatum',
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [new NotBlank()],
                'error_bubbling' => true
            ])
            // ->add('iban', null, ['label' => 'IBAN-rekeningnummer', 'error_bubbling' => true])
            ->add('address', null, ['label' => 'Adres', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('city', null, ['label' => 'Plaats', 'error_bubbling' => true, 'constraints' => [new NotBlank()]])
            ->add('postCode', null, ['label' => 'Postcode', 'error_bubbling' => true, 'constraints' => [new NotBlank()]]);
        if ($options['show_groups']) {
            $builder->add('preferredDivision', null, [
                'label' => 'Bij welke afdeling wil je je aansluiten',
                'query_builder' => function($repo) {
                    return $repo->createQueryBuilder('d')
                        ->where('d.canBeSelectedOnApplication = true')
                    ;
                },
                'required' => true
                // 'placeholder' => 'Geen voorkeur'
            ])
            ->add('contributionPerPeriodInCents', ContributionIncomeType::class, [
                'label' => 'Contributiebedrag',
                'error_bubbling' => true
            ]);
        }

        if ($options['show_work_groups']) {
            $builder->add('preferredWorkGroups', null, [
                'label' => 'Bij welke werkgroep wil je je aansluiten',
                'query_builder' => function($repo) {
                    return $repo->createQueryBuilder('d')
                        ->where('d.canBeSelectedOnApplication = true')
                    ;
                },
                'multiple' => true,
                'expanded' => true,
                // 'placeholder' => 'Geen voorkeur'
            ]);
        }
            $builder->add('accept', CheckboxType::class, [
                'label' => 'Ik heb het <a target="_blank" href="https://roodjongeren.nl/privacybeleid">privacybeleid</a> gelezen en ik ga daarmee akkoord.',
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
            'show_groups' => true,
            'show_work_groups' => true,
        ]);
    }
}
