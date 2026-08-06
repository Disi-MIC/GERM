<?php

namespace App\Form;

use App\Entity\CarteProfessionnelle;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Personnel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Le champ 'personnel' n'y figure que si l'option 'include_personnel' est activée
 * (ajout depuis la page ministérielle) ; sinon il est fixé par le contrôleur depuis
 * l'agent de la route. La date d'expiration n'est pas saisie ici : elle est calculée
 * automatiquement (délivrance + 5 ans) par CarteProfessionnelle::setDateDelivrance().
 * Le fichier de la carte n'est plus uploadé manuellement : il est généré par le
 * système (voir CarteProfessionnellePdfGenerator).
 */
class CarteProfessionnelleType extends AbstractType
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
            ->add('numero', TextType::class, ['label' => 'Numéro de carte'])
            ->add('dateDelivrance', DateType::class, [
                'label' => 'Date de délivrance',
                'widget' => 'single_text',
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutCarteProfessionnelle::class,
                'choice_label' => fn (StatutCarteProfessionnelle $s) => $s->label(),
            ])
            ->add('observations', TextareaType::class, ['label' => 'Observations', 'required' => false, 'attr' => ['rows' => 3]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CarteProfessionnelle::class,
            'include_personnel' => false,
        ]);
    }
}
