<?php

namespace App\Form\App\Note;

use App\Entity\Note;
use App\Entity\Tag;
use App\Enum\Note\NoteColorEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'placeholder' => 'Quoi de neuf ?',
                    'rows' => 12,
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Quoi de neuf ?']
            ])
            ->add('color', EnumType::class, [
                'class' => NoteColorEnum::class,
                'expanded' => true,  // 👈 Rendu sous forme de boutons radio pour nos pastilles
                'multiple' => false,
                'label' => false,
                'required' => false, // 👈 Permet de ne pas avoir de couleur
                'placeholder' => false, // 👈 Évite à Symfony de générer un bouton radio "Vide" inesthétique
            ])
            ->add('tag', EntityType::class, [
                'label' => 'Étiquette',
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false, // 👈 CRUCIAL : Génère un <select> classique pour ton CustomSelector
                'required' => false,
                'choice_attr' => function (Tag $tag) {
                    return [
                        'data-color' => $tag->getColor()?->value ?? $tag->getColor(),
                        'data-description' => $tag->getDescription()
                    ];
                },
            ])
            ->add('save', SubmitType::class, [
                'label' => '<i class="ri-save-3-line align-middle me-1"></i> Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
                'label_html' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
        ]);
    }
}
