<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, MoneyType };
use App\Entity\Member;
use Symfony\Component\Validator\Constraints\Range;

class PreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contributionPeriod', ContributionPeriodType::class, ['label' => 'Betalingsperiode'])
            ->add('contributionPerPeriodInEuros', MoneyType::class, [
                'label' => 'Bedrag',
                'constraints' => [
                    new Range(['min' => 5.0, 'notInRangeMessage' => 'Je contributie moet minimaal {{ min }} euro per jaar bedragen.'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entity_class' => Member::class
        ]);
    }
}
