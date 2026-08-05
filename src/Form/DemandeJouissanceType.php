<?php

namespace App\Form;

use App\Entity\DecisionConge;
use App\Entity\DemandeJouissance;
use App\Entity\Enum\TypeConge;
use App\Entity\Personnel;
use App\Repository\DecisionCongeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Le champ 'personnel' n'y figure que si l'option 'include_personnel' est activée
 * (ajout depuis la page ministérielle) ; sinon il est fixé par le contrôleur depuis
 * l'agent de la route (ajout depuis la fiche agent). L'option 'personnel' (distincte
 * du champ) sert à filtrer les décisions proposées sur cet agent quand il est déjà
 * connu ; sinon toutes les décisions valides sont proposées, avec le nom de l'agent
 * dans le libellé. Les champs de traitement (statut, commentaire...) ne sont pas
 * dans ce formulaire : ils sont gérés par l'action dédiée "traiter".
 */
class DemandeJouissanceType extends AbstractType
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

        $personnelConnu = $options['personnel'];

        $builder
            ->add('type', EnumType::class, [
                'label' => 'Type de congé',
                'class' => TypeConge::class,
                'choice_label' => fn (TypeConge $t) => $t->label(),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('decision', EntityType::class, [
                'label' => 'Décision de congé (obligatoire pour un congé annuel)',
                'class' => DecisionConge::class,
                'required' => false,
                'placeholder' => 'Aucune (congé non annuel)',
                'choice_label' => fn (DecisionConge $d) => $personnelConnu
                    ? sprintf('%s (expire le %s)', $d->getNumeroDecision(), $d->getDateExpiration()->format('d/m/Y'))
                    : sprintf('%s — %s (expire le %s)', $d->getPersonnel()->getNomComplet(), $d->getNumeroDecision(), $d->getDateExpiration()->format('d/m/Y')),
                'query_builder' => function (DecisionCongeRepository $r) use ($personnelConnu) {
                    $qb = $r->createQueryBuilder('d')
                        ->andWhere('d.dateExpiration >= :aujourdhui')
                        ->setParameter('aujourdhui', new \DateTimeImmutable('today'))
                        ->orderBy('d.dateExpiration', 'ASC');

                    if ($personnelConnu) {
                        $qb->andWhere('d.personnel = :personnel')->setParameter('personnel', $personnelConnu);
                    }

                    return $qb;
                },
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
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
            'data_class' => DemandeJouissance::class,
            'include_personnel' => false,
            'personnel' => null,
        ]);
    }
}
