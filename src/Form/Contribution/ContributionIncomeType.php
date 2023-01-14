<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, MoneyType };
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, MoneyType};
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use App\Entity\ChosenContribution;

class ContributionIncomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $minimumContribution = 9; // default contribution
        $builder
            ->add('contributionAmount', MoneyType::class, [
                'label' => 'Contributie:',
                'divisor' => 100,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => $minimumContribution * 100,
                        'message' => "Er is iets verkeerd gegaan. Heeft u minimaal een contributie hoger dan $minimumContribution euro per kwartaal ingesteld?",
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entity_class' => ChosenContribution::class,
        ]);
    }
}
