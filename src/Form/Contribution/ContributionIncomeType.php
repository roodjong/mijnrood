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
        $division = $options['division'];
        $divisionName = $division->getName();
        // Developer disclaimer for this ugly conditional:
        // normally I would add a new attribute to the division
        // for the preferred contribution, but it is likely that we will
        // have one big national contribution very soon, so this will be
        // redundant.
        $minimumContribution = 3; // default contribution
        $divisionContribution = [
            'Amsterdam' => 15,
            'Utrecht' => 9,
            'Oost-Brabant' => 9,
            'Noord' => 9,
            'Overijssel' => 7.50,
        ];
        if (isset($divisionContribution[$divisionName])) {
            $minimumContribution = $divisionContribution[$divisionName];
        }
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
            'division' => null,
        ]);
    }
}
