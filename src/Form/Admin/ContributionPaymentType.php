<?php

namespace App\Form\Admin;

use App\Entity\ContributionPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\{ DateType, MoneyType, ChoiceType };

class ContributionPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('paymentTime', DateType::class, ['label' => 'Datum van betaling', 'required' => true, 'widget' => 'single_text'])
            ->add('amountInEuros', MoneyType::class, ['label' => 'Bedrag', 'attr' => ['step' => '0.01'], 'required' => true])
            ->add('molliePaymentId', null, ['label' => 'Payment-ID (Mollie)', 'disabled' => true, 'required' => false])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'In afwachting' => ContributionPayment::STATUS_PENDING,
                    'Betaald' => ContributionPayment::STATUS_PAID,
                    'Mislukt' => ContributionPayment::STATUS_FAILED,
                    'Terugbetaald' => ContributionPayment::STATUS_REFUNDED
                ]
            ])
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
