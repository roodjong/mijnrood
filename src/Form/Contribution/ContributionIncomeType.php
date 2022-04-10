<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, MoneyType };

class ContributionIncomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contributionAmount', ChoiceType::class, [
                'error_bubbling' => true,
                'label' => 'Maandinkomen',
                'choices' => [
                    'Tot en met €2000 (ik betaal €7,50 contributie per kwartaal)' => 750,
                    '€2000-€3499 (ik betaal €15,00 contributie per kwartaal)' => 1500,
                    '€3500 en daarboven (ik betaal €22,50 contributie per kwartaal)' => 2250,
                    'ik betaal een hogere contributie, namelijk:' => 0,
                ],
                'expanded' => true
            ])
            ->add('otherAmount', MoneyType::class, [
                'error_bubbling' => true,
                'currency' => 'EUR',
                'label' => 'Hoger contributiebedrag',
                'divisor' => 100,
                'required' => false,
                'attr' => [
                    'min' => 2250
                ],
                'constraints' => new Assert\GreaterThan([
                    'value' => 22.50,
                    'message' => 'Als je een hoger bedrag selecteert, moet dit hoger dan {{ compared_value }} zijn'
                ])
            ]);

        $builder->addModelTransformer(new CallbackTransformer(
            fn($model) => [
                'contributionAmount' => $model > 2250 ? null : $model,
                'otherAmount' => $model > 2250 ? $model : null
            ],
            fn($norm) => $norm['contributionAmount'] === 0 ? $norm['otherAmount'] : $norm['contributionAmount']
        ));
    }

}
