<?php

namespace App\Form\Auth\Profile;

use App\Entity\Tag;
use App\Enum\TagColorEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'étiquette',
                'attr' => ['placeholder' => 'Nom de l\'étiquette', 'class' => 'form-control']
            ])
            ->add('description', TextType::class, [
                'label' => 'Description de l\'étiquette',
                'required' => false,
                'attr' => ['placeholder' => 'Description de l\'étiquette', 'class' => 'form-control']
            ])
            ->add('color', EnumType::class, [
                // Appel direct de ta méthode
                // 'choices' => TagColorEnum::choices(),
                'class' => TagColorEnum::class, 
                'expanded' => true,
                'multiple' => false,
                'label' => 'Couleur de l\'étiquette',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['type' => 'submit', 'class' => 'btn btn-primary']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Tag::class]);
    }
}