<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType, TextType };
use App\Entity\ChosenContribution;

class ContributionIncomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contributionAmount', ChoiceType::class, [
                'label' => 'Maandinkomen:', 'choices' => [
                    'Tot en met €2000 (ik betaal €7,50 contributie per kwartaal)' => 750,
                    '€2000-€3499 (ik betaal €15,00 contributie per kwartaal)' => 1500,
                    '€3500 en daarboven (ik betaal €22,50 contributie per kwartaal)' => 2250,
                    'ik betaal een hogere contributie, namelijk:' => 0,
                ],
                'expanded' => true
            ])
            ->add('otherAmount', TextType::class, ['label' => '€', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entity_class' => ChosenContribution::class
        ]);
    }
}
