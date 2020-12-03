<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, MoneyType };
use App\Entity\Member;

class PreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contributionPeriod', ChoiceType::class, [
                'label' => 'Betalingsperiode',
                'choices' => [
                    'Maandelijks' => 0,
                    'Per kwartaal' => 1,
                    'Jaarlijks' => 2
                ]
            ])
            ->add('contributionPerPeriodInEuros', MoneyType::class, ['label' => 'Hoogte'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entity_class' => Member::class
        ]);
    }
}
