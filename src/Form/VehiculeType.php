<?php

namespace App\Form;

use App\Entity\Enum\Carburant;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use App\Entity\Personnel;
use App\Entity\Service;
use App\Entity\Vehicule;
use App\Repository\ListeValeurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('immatriculation', TextType::class, ['label' => 'Immatriculation'])
            ->add('type', EntityType::class, [
                'label' => 'Type de véhicule',
                'class' => ListeValeur::class,
                'choice_label' => fn (ListeValeur $lv) => $lv->getLibelle().($lv->isActif() ? '' : ' (inactif)'),
                'query_builder' => fn (ListeValeurRepository $r) => $r->createQueryBuilder('lv')
                    ->andWhere('lv.categorie = :cat')
                    ->setParameter('cat', CategorieListeValeur::TYPE_VEHICULE)
                    ->orderBy('lv.libelle', 'ASC'),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('marque', TextType::class, ['label' => 'Marque'])
            ->add('modele', TextType::class, ['label' => 'Modèle'])
            ->add('numeroChassis', TextType::class, ['label' => 'Numéro de châssis', 'required' => false])
            ->add('carburant', EnumType::class, [
                'label' => 'Carburant',
                'class' => Carburant::class,
                'choice_label' => fn (Carburant $c) => $c->label(),
                'required' => false,
                'placeholder' => 'Sélectionner...',
            ])
            ->add('dateAcquisition', DateType::class, [
                'label' => "Date d'acquisition",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('valeurAcquisition', MoneyType::class, [
                'label' => "Valeur d'acquisition",
                'currency' => false,
                'required' => false,
            ])
            ->add('kilometrage', IntegerType::class, ['label' => 'Kilométrage', 'required' => false])
            ->add('assuranceJusquau', DateType::class, [
                'label' => "Assurance valable jusqu'au",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('visiteTechniqueJusquau', DateType::class, [
                'label' => "Visite technique valable jusqu'au",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('etat', EntityType::class, [
                'label' => 'État',
                'class' => ListeValeur::class,
                'choice_label' => fn (ListeValeur $lv) => $lv->getLibelle().($lv->isActif() ? '' : ' (inactif)'),
                'query_builder' => fn (ListeValeurRepository $r) => $r->createQueryBuilder('lv')
                    ->andWhere('lv.categorie = :cat')
                    ->setParameter('cat', CategorieListeValeur::ETAT_VEHICULE)
                    ->orderBy('lv.libelle', 'ASC'),
            ])
            ->add('service', EntityType::class, [
                'label' => 'Service / Direction',
                'class' => Service::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner...',
            ])
            ->add('chauffeurAffecte', EntityType::class, [
                'label' => 'Chauffeur affecté',
                'class' => Personnel::class,
                'choice_label' => 'nomComplet',
                'required' => false,
                'placeholder' => 'Non affecté',
            ])
            ->add('observations', TextareaType::class, ['label' => 'Observations', 'required' => false, 'attr' => ['rows' => 3]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}
