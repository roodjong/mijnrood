<?php

namespace App\Form;

use App\Entity\ContributionPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class ContributionPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('paymentTime', DateType::class, ['label' => 'Datum van betaling', 'required' => true, 'widget' => 'single_text'])
            ->add('amountInEuros', MoneyType::class, ['label' => 'Bedrag', 'attr' => ['step' => '0.01'], 'required' => true])
            ->add('molliePaymentId', null, ['label' => 'Payment-ID (Mollie)', 'disabled' => true, 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'block_prefix' => 'collection_table_entry',
            'data_class' => ContributionPayment::class,
        ]);
    }

}
