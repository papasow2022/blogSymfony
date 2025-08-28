<?php

namespace App\Form;

use App\Entity\CommentReport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reason', TextareaType::class, [
            'label' => 'Raison du signalement (optionnel)',
            'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Expliquez pourquoi vous signalez ce commentaire...'],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommentReport::class,
        ]);
    }
}
