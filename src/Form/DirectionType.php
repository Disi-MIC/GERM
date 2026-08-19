<?php

namespace App\Form;

use App\Entity\Direction;
use App\Entity\Personnel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => ['placeholder' => 'ex: DSI, DRH, DIRCOM'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de la direction',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('directeur', EntityType::class, [
                'label' => 'Directeur',
                'class' => Personnel::class,
                'choice_label' => 'nomComplet',
                'placeholder' => 'Aucun',
                'required' => false,
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Direction active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Direction::class,
        ]);
    }
}
