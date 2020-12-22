<?php

namespace App\Form\Contribution;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ ChoiceType };
use App\Entity\Member;

class ContributionPeriodType extends ChoiceType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choices' => [
                // 'Maandelijks' => Member::PERIOD_MONTHLY,
                // 'Per kwartaal' => Member::PERIOD_QUARTERLY,
                'Jaarlijks' => Member::PERIOD_ANNUALLY
            ]
        ]);
    }
}
