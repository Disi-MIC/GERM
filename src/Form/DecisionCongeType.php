<?php

namespace App\Form;

use App\Entity\DecisionConge;
use App\Entity\Personnel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Le champ 'personnel' n'y figure que si l'option 'include_personnel' est activée
 * (ajout depuis la page ministérielle) ; sinon il est fixé par le contrôleur depuis
 * l'agent de la route.
 */
class DecisionCongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_personnel']) {
            $builder->add('personnel', EntityType::class, [
                'label' => 'Agent',
                'class' => Personnel::class,
                'choice_label' => 'nomComplet',
                'placeholder' => 'Sélectionner...',
            ]);
        }

        $builder
            ->add('numeroDecision', TextType::class, ['label' => 'Numéro de décision'])
            ->add('dateDecision', DateType::class, [
                'label' => "Date d'octroi",
                'widget' => 'single_text',
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => "Date d'expiration",
                'widget' => 'single_text',
            ])
            ->add('observations', TextareaType::class, ['label' => 'Observations', 'required' => false, 'attr' => ['rows' => 3]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DecisionConge::class,
            'include_personnel' => false,
        ]);
    }
}
