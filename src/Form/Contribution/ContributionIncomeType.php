<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, MoneyType };
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContributionIncomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        $max_amount = 0;
        foreach ($options['contribution_tiers']['tiers'] as $tier) {
            $choices[$tier['description']] = $tier['amount'] === null ? 0 : $tier['amount'];

            if ($tier['amount'] > $max_amount) {
                $max_amount = $tier['amount'];
            }
        }

        $builder
            ->add('contributionAmount', ChoiceType::class, [
                'error_bubbling' => true,
                'label' => 'Maandinkomen',
                'choices' => $choices,
                'expanded' => true
            ])
            ->add('otherAmount', MoneyType::class, [
                'error_bubbling' => true,
                'currency' => 'EUR',
                'label' => 'Hoger contributiebedrag',
                'divisor' => 100,
                'required' => false,
                'attr' => [
                    'min' => $max_amount
                ],
                'constraints' => new Assert\GreaterThan([
                    'value' => $max_amount / 100,
                    'message' => 'Als je een hoger bedrag selecteert, moet dit hoger dan {{ compared_value }} zijn'
                ])
            ]);

        $builder->addModelTransformer(new CallbackTransformer(
            fn($model) => [
                'contributionAmount' => $model > $max_amount ? null : $model,
                'otherAmount' => $model > $max_amount ? $model : null
            ],
            fn($norm) => $norm['contributionAmount'] === 0 ? $norm['otherAmount'] : $norm['contributionAmount']
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'contribution_tiers'
        ]);
    }
}
