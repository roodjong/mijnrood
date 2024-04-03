<?php

namespace App\Form;

use App\Entity\SupportMembershipApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Contribution\ContributionPeriodType;
use Symfony\Component\Form\Extension\Core\Type\{ CheckboxType, ChoiceType, NumberType };
use App\Validator\Age;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class SupportMembershipApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', null, ['label' => 'Voornaam', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('lastName', null, ['label' => 'Achternaam', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('email', null, ['label' => 'E-mailadres', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('phone', null, ['label' => 'Telefoonnummer', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('dateOfBirth', null, [
                'label' => 'Geboortedatum',
                'required' => true,
                'widget' => 'choice',
                'years' => range(1900, 2021),
                'constraints' => [new NotBlank],
                'error_bubbling' => true
            ])
            ->add('address', null, ['label' => 'Adres', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('city', null, ['label' => 'Plaats', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('postCode', null, ['label' => 'Postcode', 'error_bubbling' => true, 'constraints' => [new NotBlank]])
            ->add('contributionPeriod', ChoiceType::class, [
                'label' => 'Betaling per',
                'choices' => [
                    'Maand' => SupportMembershipApplication::PERIOD_MONTHLY,
                    // 'Kwartaal' => SupportMembershipApplication::PERIOD_QUARTERLY,
                    // 'Jaar' => SupportMembershipApplication::PERIOD_ANNUALLY
                ],
                'constraints' => [new NotBlank],
                'error_bubbling' => true
            ])
            ->add('contributionPerPeriodInEuros', NumberType::class, [
                'label' => 'Contributie per {period}',
                'html5' => true,
                'scale' => 2,
                'input' => 'number',
                'constraints' => [new Range(['min' => 5, 'minMessage' => 'De contributie bedraagt ten minste 5 euro per maand.'])],
                'error_bubbling' => true
            ])
            ->add('acceptPrivacy', CheckboxType::class, [
                'label' => 'Ik heb het <a target="_blank" href="https://roodjongeren.nl/privacybeleid">privacybeleid</a> gelezen en ik ga daarmee akkoord.',
                'label_html' => true,
                'mapped' => false,
                'required' => true,
                'error_bubbling' => true,
                'constraints' => [new IsTrue(['message' => 'Je moet akkoord gaan met het privacybeleid van ROOD, tenzij je jonger bent dan 16.'])],
                'error_bubbling' => true
            ])
            ->add('acceptRecurringPayments', CheckboxType::class, [
                'label' => 'Ik ga ermee akkoord dat ROOD periodiek door middel van automatische incasso mijn contributie int.',
                'label_html' => true,
                'mapped' => false,
                'required' => true,
                'error_bubbling' => true,
                'constraints' => [new IsTrue(['message' => 'Je moet akkoord gaan met deze voorwaarde.'])],
                'error_bubbling' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SupportMembershipApplication::class,
        ]);
    }
}
