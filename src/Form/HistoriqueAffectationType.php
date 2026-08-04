<?php

namespace App\Form;

use App\Entity\Enum\TypeMouvementCarriere;
use App\Entity\HistoriqueAffectation;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'ajout d'un mouvement de carrière. Le champ 'personnel' n'y figure pas :
 * il est fixé par le contrôleur depuis l'agent de la route.
 */
class HistoriqueAffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeMouvement', EnumType::class, [
                'label' => 'Type de mouvement',
                'class' => TypeMouvementCarriere::class,
                'choice_label' => fn (TypeMouvementCarriere $t) => $t->label(),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('service', EntityType::class, [
                'label' => 'Service / Direction',
                'class' => Service::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner...',
            ])
            ->add('fonction', TextType::class, ['label' => 'Fonction'])
            ->add('grade', TextType::class, ['label' => 'Grade', 'required' => false])
            ->add('dateEffet', DateType::class, [
                'label' => "Date d'effet",
                'widget' => 'single_text',
            ])
            ->add('numeroDecision', TextType::class, ['label' => 'Numéro de décision', 'required' => false])
            ->add('dateDecision', DateType::class, [
                'label' => 'Date de la décision',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('observations', TextareaType::class, ['label' => 'Observations', 'required' => false, 'attr' => ['rows' => 3]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoriqueAffectation::class,
        ]);
    }
}
