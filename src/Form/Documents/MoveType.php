<?php

namespace App\Form\Documents;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\EntityTreeType;
use App\Entity\DocumentFolder;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class MoveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('type', HiddenType::class)
            ->add('parent', EntityTreeType::class, [
                'label' => 'Doel',
                'choices' => $options['choices'],
                'parent_method_name' => 'getParent',
                'children_method_name' => 'getSubFolders',
                // 'label_method' => fn($entity) => $entity->getName(),
                'class' => DocumentFolder::class,
                'max_depth' => 4,
                'placeholder' => '(Hoofdmap)',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [],
            'folder' => false
        ]);
    }
}
