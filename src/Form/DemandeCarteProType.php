<?php

namespace App\Form;

use App\Entity\CarteProfessionnelle;
use App\Entity\DemandeCartePro;
use App\Entity\Enum\TypeDemandeCartePro;
use App\Entity\Personnel;
use App\Repository\CarteProfessionnelleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Le champ 'personnel' n'y figure que si l'option 'include_personnel' est activée
 * (ajout depuis la page ministérielle) ; sinon il est fixé par le contrôleur depuis
 * l'agent de la route. L'option 'personnel' (distincte du champ) sert à filtrer les
 * cartes proposées sur cet agent quand il est déjà connu ; sinon toutes les cartes
 * sont proposées, avec le nom de l'agent dans le libellé. Contrairement à la décision
 * de congé, aucun filtre sur la validité/date n'est appliqué : un renouvellement ou
 * une perte/vol doit pouvoir référencer une carte expirée ou déjà marquée perdue/volée.
 * Les champs de traitement (statut, commentaire...) ne sont pas dans ce formulaire :
 * ils sont gérés par l'action dédiée "traiter".
 */
class DemandeCarteProType extends AbstractType
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
            ->add('typeDemande', EnumType::class, [
                'label' => 'Type de demande',
                'class' => TypeDemandeCartePro::class,
                'choice_label' => fn (TypeDemandeCartePro $t) => $t->label(),
                'placeholder' => 'Sélectionner...',
            ])
            ->add('carteReference', EntityType::class, [
                'label' => 'Carte actuelle (obligatoire pour un renouvellement ou une perte/vol)',
                'class' => CarteProfessionnelle::class,
                'required' => false,
                'placeholder' => 'Aucune (nouvelle carte)',
                'choice_label' => fn (CarteProfessionnelle $c) => $personnelConnu
                    ? sprintf('%s (expire le %s)', $c->getNumero(), $c->getDateExpiration()->format('d/m/Y'))
                    : sprintf('%s — %s (expire le %s)', $c->getPersonnel()->getNomComplet(), $c->getNumero(), $c->getDateExpiration()->format('d/m/Y')),
                'query_builder' => function (CarteProfessionnelleRepository $r) use ($personnelConnu) {
                    $qb = $r->createQueryBuilder('c')
                        ->orderBy('c.dateDelivrance', 'DESC');

                    if ($personnelConnu) {
                        $qb->andWhere('c.personnel = :personnel')->setParameter('personnel', $personnelConnu);
                    }

                    return $qb;
                },
            ])
            ->add('motif', TextareaType::class, ['label' => 'Motif', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('fichier', FileType::class, [
                'label' => 'Pièce justificative (ex. déclaration de perte/vol)',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Assert\File(maxSize: '5M', mimeTypes: self::MIME_TYPES, mimeTypesMessage: self::MIME_TYPES_MESSAGE)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DemandeCartePro::class,
            'include_personnel' => false,
            'personnel' => null,
        ]);
    }
}
