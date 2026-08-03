<?php

namespace App\Form;

use App\Entity\Enum\CategorieListeValeur;
use App\Entity\Enum\Sexe;
use App\Entity\Enum\StatutPersonnel;
use App\Entity\ListeValeur;
use App\Entity\Personnel;
use App\Entity\Service;
use App\Repository\ListeValeurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule', TextType::class, ['label' => 'Matricule'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('sexe', EnumType::class, [
                'label' => 'Sexe',
                'class' => Sexe::class,
                'choice_label' => fn (Sexe $s) => $s->label(),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('fonction', TextType::class, ['label' => 'Fonction'])
            ->add('grade', TextType::class, ['label' => 'Grade', 'required' => false])
            ->add('typeContrat', EntityType::class, [
                'label' => 'Type de contrat',
                'class' => ListeValeur::class,
                'choice_label' => fn (ListeValeur $lv) => $lv->getLibelle().($lv->isActif() ? '' : ' (inactif)'),
                'query_builder' => fn (ListeValeurRepository $r) => $r->createQueryBuilder('lv')
                    ->andWhere('lv.categorie = :cat')
                    ->setParameter('cat', CategorieListeValeur::TYPE_CONTRAT)
                    ->orderBy('lv.libelle', 'ASC'),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('dateEmbauche', DateType::class, [
                'label' => "Date d'embauche",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutPersonnel::class,
                'choice_label' => fn (StatutPersonnel $s) => $s->label(),
            ])
            ->add('service', EntityType::class, [
                'label' => 'Service / Direction',
                'class' => Service::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner...',
            ])
            ->add('telephone', TelType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'Email professionnel', 'required' => false])
            ->add('adresse', TextareaType::class, ['label' => 'Adresse', 'required' => false, 'attr' => ['rows' => 2]])
            ->add('observations', TextareaType::class, ['label' => 'Observations', 'required' => false, 'attr' => ['rows' => 3]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnel::class,
        ]);
    }
}
