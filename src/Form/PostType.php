<?php

namespace App\Form;

use App\Entity\Post;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Titre de l\'article'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['class' => 'form-control', 'rows' => 10, 'placeholder' => 'Contenu de l\'article'],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tags', TextType::class, [
                'label' => 'Tags',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'php, symfony, web (séparés par des virgules)'
                ],
                'help' => 'Séparez les tags par des virgules',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image d\'illustration (facultatif)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/gif,image/webp'],
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 2 Mo.',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG, GIF, WEBP).',
                    ]),
                    new Image([
                        'minWidth' => 200,
                        'minHeight' => 200,
                        'maxWidth' => 4000,
                        'maxHeight' => 4000,
                        'minWidthMessage' => 'L\'image est trop petite ({{ width }}px). Largeur minimale: {{ min_width }}px.',
                        'minHeightMessage' => 'L\'image est trop petite ({{ height }}px). Hauteur minimale: {{ min_height }}px.',
                        'maxWidthMessage' => 'L\'image est trop large ({{ width }}px). Largeur maximale: {{ max_width }}px.',
                        'maxHeightMessage' => 'L\'image est trop haute ({{ height }}px). Hauteur maximale: {{ max_height }}px.',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}


