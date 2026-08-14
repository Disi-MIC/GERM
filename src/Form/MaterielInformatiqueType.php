<?php

namespace App\Form;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Entity\Service;
use App\Repository\ListeValeurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaterielInformatiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroInventaire', TextType::class, ['label' => "N° d'inventaire"])
            ->add('type', EntityType::class, [
                'label' => 'Type de matériel',
                'class' => ListeValeur::class,
                'choice_label' => fn (ListeValeur $lv) => $lv->getLibelle().($lv->isActif() ? '' : ' (inactif)'),
                'query_builder' => fn (ListeValeurRepository $r) => $r->createQueryBuilder('lv')
                    ->andWhere('lv.categorie = :cat')
                    ->setParameter('cat', CategorieListeValeur::TYPE_MATERIEL)
                    ->orderBy('lv.libelle', 'ASC'),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('marque', TextType::class, ['label' => 'Marque'])
            ->add('modele', TextType::class, ['label' => 'Modèle'])
            ->add('numeroSerie', TextType::class, ['label' => 'Numéro de série', 'required' => false])
            ->add('specifications', TextareaType::class, [
                'label' => 'Caractéristiques techniques',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'CPU, RAM, disque, OS...'],
            ])
            ->add('dateAcquisition', DateType::class, [
                'label' => "Date d'acquisition",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('fournisseur', TextType::class, ['label' => 'Fournisseur', 'required' => false])
            ->add('dateMiseEnService', DateType::class, [
                'label' => 'Date de mise en service',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('etat', EntityType::class, [
                'label' => 'État',
                'class' => ListeValeur::class,
                'choice_label' => fn (ListeValeur $lv) => $lv->getLibelle().($lv->isActif() ? '' : ' (inactif)'),
                'query_builder' => fn (ListeValeurRepository $r) => $r->createQueryBuilder('lv')
                    ->andWhere('lv.categorie = :cat')
                    ->setParameter('cat', CategorieListeValeur::ETAT_MATERIEL)
                    ->orderBy('lv.libelle', 'ASC'),
            ])
            ->add('service', EntityType::class, [
                'label' => 'Service / Direction',
                'class' => Service::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Non renseigné',
                'help' => "Dérivé automatiquement dès qu'un agent est affecté ci-dessous. Pas nécessaire pour un matériel en stock ou réformé.",
            ])
            ->add('affecteA', EntityType::class, [
                'label' => 'Affecté à (agent)',
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
            'data_class' => MaterielInformatique::class,
        ]);
    }
}
