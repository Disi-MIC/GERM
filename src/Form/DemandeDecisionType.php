<?php

namespace App\Form;

use App\Entity\DemandeDecision;
use App\Entity\Personnel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Le champ 'personnel' n'y figure que si l'option 'include_personnel' est activée
 * (ajout depuis la page ministérielle) ; sinon il est fixé par le contrôleur depuis
 * l'agent de la route. Les champs de traitement (statut, numéro/dates de la nouvelle
 * décision...) ne sont pas dans ce formulaire : ils sont gérés par l'action dédiée
 * "traiter".
 */
class DemandeDecisionType extends AbstractType
{
    private const MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
    private const MIME_TYPES_MESSAGE = 'Merci de déposer un fichier PDF, JPEG ou PNG (5 Mo maximum).';

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
            ->add('dateDerniereDecision', DateType::class, [
                'label' => 'Date de la dernière décision (si applicable)',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('numeroDerniereDecision', TextType::class, [
                'label' => 'Numéro de la dernière décision (si applicable)',
                'required' => false,
            ])
            ->add('motif', TextareaType::class, ['label' => 'Motif', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('pieceJustificative1', FileType::class, [
                'label' => 'Pièce justificative 1',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Assert\File(maxSize: '5M', mimeTypes: self::MIME_TYPES, mimeTypesMessage: self::MIME_TYPES_MESSAGE)],
            ])
            ->add('pieceJustificative2', FileType::class, [
                'label' => 'Pièce justificative 2',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Assert\File(maxSize: '5M', mimeTypes: self::MIME_TYPES, mimeTypesMessage: self::MIME_TYPES_MESSAGE)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeDecision::class,
            'include_personnel' => false,
        ]);
    }
}
