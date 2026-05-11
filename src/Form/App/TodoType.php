<?php

namespace App\Form\App;

use App\Entity\Tag;
use App\Entity\Task;
use App\Enum\Task\TaskPriorityEnum;
use App\Enum\Task\TaskStateEnum;
use App\Enum\Task\TodoDueDateEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TodoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /** @var Task|null $task */
        $task = $builder->getData();
        $hasDueDate = $task instanceof Task && $task->getDueDate() !== null;

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Que devez-vous faire ?']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ajoutez des détails supplémentaires...'
                ]
            ])
            ->add('is_completed', CheckboxType::class, [
                'label' => 'Marquer comme terminée',
                'required' => false,
                // Ces classes dépendent de ton framework CSS (ex: Bootstrap form-switch)
                // 'attr' => ['class' => 'form-check-input'], 
                // 'label_attr' => ['class' => 'form-check-label']
            ]);

        if ($hasDueDate) {
            // Si une date existe déjà, on affiche un sélecteur de date classique
            $builder->add('due_date', DateType::class, [
                'label' => 'Date d\'échéance',
                'required' => false,
                'widget' => 'single_text', // Affiche un input type="date" natif
                'mapped' => true, // Symfony hydrate l'entité automatiquement
            ]);
        } else {
            // Sinon, on propose l'Enumération rapide
            $builder->add('due_date', EnumType::class, [
                'label' => 'Date d\'échéance',
                'required' => false,
                'mapped' => false, // On gèrera l'hydratation dans le controller
                'class' => TodoDueDateEnum::class,
                'expanded' => false,
                'choice_label' => function (?TodoDueDateEnum $dueDate) {
                    return $dueDate ? TodoDueDateEnum::getMap($dueDate) ?? $dueDate->value : 'Aucune échéance';
                },
                'placeholder' => 'Sélectionnez une échéance',
            ]);
        }

        $builder
            ->add('priority', EnumType::class, [
                'label' => 'Priorité',
                'class' => TaskPriorityEnum::class,
                'required' => false,
                'expanded' => false, // ⚠️ Requis : génère un <select> pour le CustomSelector
                'choice_attr' => function (?TaskPriorityEnum $priority) {
                    return $priority ? [
                        // On injecte le niveau de priorité comme description
                        'data-description' => 'Niveau : ' . ucfirst(TaskPriorityEnum::getMap($priority) ?? 'Inconnu'),
                        // Si tu as un système de couleur, tu peux l'ajouter ici
                        // 'data-color' => match($priority) { TaskPriorityEnum::High => 'danger', ... }
                    ] : [];
                },
                'choice_label' => function (?TaskPriorityEnum $priority) {
                    return $priority ? TaskPriorityEnum::getMap($priority) ?? $priority->value : 'Aucune priorité';
                },
            ])
            ->add('state', EnumType::class, [
                'label' => 'Statut',
                'class' => TaskStateEnum::class,
                'expanded' => false,
                'choice_label' => function (?TaskStateEnum $state) {
                    return $state ? TaskStateEnum::getMap($state) ?? $state->value : 'Aucun statut';
                },
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}