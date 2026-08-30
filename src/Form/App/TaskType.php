<?php

// src/Form/App/TaskType.php

namespace App\Form\App;

use App\Entity\Tag;
use App\Entity\Task;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Que devez-vous faire ?']
            ])
            ->add('is_completed', CheckboxType::class, [
                'label' => 'Marquer comme terminée',
                'required' => false,
            ])

            // 👇 CORRECTION ICI 👇
            ->add('due_date', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text', // Affiche le DatePicker natif HTML5
                'input' => 'datetime_immutable', // ⚠️ INDISPENSABLE pour matcher l'entité
                'required' => false,
            ])

            ->add('tag', EntityType::class, [
                'label' => 'Étiquettes',
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
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
            'data_class' => Task::class, // On s'assure qu'il pointe bien vers Task
        ]);
    }
}