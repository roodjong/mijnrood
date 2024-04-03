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
        foreach ($options['contribution']['tiers'] as $tier) {
            if ($tier['amount'] === null) {
                $choices[$tier['description']] = 0;
            } else {
                $description = $tier['description'] . ' (ik betaal €' . number_format($tier['amount'] / 100, 2, ',') . ' contributie per kwartaal)';
                $choices[$description] = $tier['amount'];
            }

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
                    'min' => $max_amount / 100
                ],
                'constraints' => new Assert\GreaterThan([
                    'value' => $max_amount,
                    'message' => 'Als je een hoger bedrag selecteert, moet dit hoger dan €' . number_format($max_amount / 100, 2, ',') . ' zijn.'
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
            'contribution'
        ]);
    }
}
