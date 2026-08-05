<?php

namespace App\Form;

use App\Entity\Delegation;
use App\Entity\Enum\RoleDelegable;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Le délégant (option 'delegant') n'est jamais proposé comme délégataire —
 * il est fixé par le contrôleur sur l'utilisateur connecté.
 */
class DelegationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $delegant = $options['delegant'];

        $builder
            ->add('delegataire', EntityType::class, [
                'label' => 'Agent délégataire',
                'class' => User::class,
                'choice_label' => fn (User $u) => sprintf('%s (%s)', $u->getNomComplet(), $u->getEmail()),
                'placeholder' => 'Sélectionner...',
                'query_builder' => function (UserRepository $r) use ($delegant) {
                    $qb = $r->createQueryBuilder('u')
                        ->andWhere('u.actif = true')
                        ->orderBy('u.nom', 'ASC');

                    if ($delegant) {
                        $qb->andWhere('u != :delegant')->setParameter('delegant', $delegant);
                    }

                    return $qb;
                },
            ])
            ->add('roleDelegue', EnumType::class, [
                'label' => 'Rôle à déléguer',
                'class' => RoleDelegable::class,
                'choice_label' => fn (RoleDelegable $r) => $r->label(),
                'placeholder' => 'Sélectionner...',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Delegation::class,
            'delegant' => null,
        ]);
    }
}
