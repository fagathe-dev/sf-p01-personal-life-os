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
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Quoi de neuf ?',
                    'rows' => 12,
                ],
            ])
            ->add('is_pinned', CheckboxType::class, [
                'label' => 'Épingler la note',
                'required' => false,
                // Ces classes dépendent de ton framework CSS (ex: Bootstrap form-switch)
                // 'attr' => ['class' => 'form-check-input'], 
                // 'label_attr' => ['class' => 'form-check-label']
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Quoi de neuf ?']
            ])
            ->add('color', EnumType::class, [
                // Appel direct de ta méthode
                // 'choices' => TagColorEnum::choices(),
                'class' => NoteColorEnum::class,
                'expanded' => true,
                'multiple' => false,
                'label' => false,
            ])
            ->add('tags', EntityType::class, [
                'label' => 'Étiquettes',
                'class' => Tag::class,
                // ⚠️ J'ai changé 'id' par 'name' (ou 'title' selon ton entité Tag)
                // pour que le texte dans le dropdown soit lisible
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false, // ⚠️ Requis : génère un <select> pour le CustomSelector
                'required' => false,
                'choice_attr' => function (Tag $tag) {
                    return [
                        // On suppose que getColor() retourne un Enum ou une string
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
