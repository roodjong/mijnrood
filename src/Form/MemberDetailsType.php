<?php

namespace App\Form;

use App\Entity\Division;
use App\Entity\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class MemberDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', TextType::class, ['disabled' => true, 'label' => 'Lidnummer'])
            ->add('division', EntityType::class, ['class' => Division::class,
                                                  'label' => 'Afdeling'])
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('phone')
            ->add('address')
            ->add('city')
            ->add('postCode')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Member::class,
        ]);
    }
}
