<?php

namespace App\Form;

use App\Entity\Personnel;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de gestion des comptes agents (accès à l'application).
 * Le mot de passe n'est requis qu'à la création (voir option 'is_new').
 *
 * Le champ 'personnel' est volontairement non-mappé (l'association est portée
 * par le côté propriétaire Personnel::$user) : le contrôleur synchronise
 * manuellement la relation bidirectionnelle après validation, et applique la
 * règle métier "obligatoire sauf pour un compte super administrateur".
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, ['label' => 'Email de connexion'])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'help' => 'Obligatoire uniquement si aucune fiche agent n\'est liée ci-dessous — sinon dérivé automatiquement de cette fiche.',
            ])
            ->add('prenom', TextType::class, ['label' => 'Prénom', 'required' => false])
            ->add('personnel', EntityType::class, [
                'label' => 'Fiche agent liée',
                'class' => Personnel::class,
                'choice_label' => 'nomComplet',
                'placeholder' => 'Aucune (réservé à un compte super administrateur)',
                'required' => false,
                'mapped' => false,
                'data' => $options['personnel_actuel'],
                'choices' => $options['personnel_disponibles'],
                'help' => 'Chaque compte doit être rattaché à une fiche agent, sauf le compte super administrateur.',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                // 'ROLE_AGENT' est volontairement absent : déjà accordé
                // automatiquement à tout compte (voir User::getRoles()) — le
                // cocher ou non n'aurait aucun effet, ce qui induirait en
                // erreur quiconque configure un compte. Ne laisser que les
                // rôles qui changent réellement les accès.
                'choices' => [
                    'Responsable RH' => User::ROLE_RH_RESPONSABLE,
                    'Gestion du personnel' => User::ROLE_RH_PERSONNEL,
                    'Gestion des congés' => User::ROLE_RH_CONGE,
                    'Gestion des cartes professionnelles' => User::ROLE_RH_CARTE_PRO,
                    'Gestion du stock informatique' => User::ROLE_IT_STOCK,
                    'Technicien informatique (traitement des tickets)' => User::ROLE_IT_TICKETS,
                    'Responsable informatique' => User::ROLE_IT_RESPONSABLE,
                    'Responsable de service (Chefs de service)' => User::ROLE_RESPONSABLE_SERVICE,
                    'Direction ministérielle (Directeurs)' => User::ROLE_DIRECTION_MINISTERIELLE,
                    'Autorité (SG / DC / Ministre)' => User::ROLE_AUTORITE,
                    'Super administrateur' => User::ROLE_SUPERADMIN,
                ],
                'multiple' => true,
                'expanded' => true,
                'help' => "Un agent sans rôle coché conserve un accès de base (Mon espace : profil, carrière, congés, carte professionnelle...) sans rubrique RH.",
            ])
            ->add('actif', CheckboxType::class, ['label' => 'Compte actif', 'required' => false])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['is_new'] ? 'Mot de passe' : 'Nouveau mot de passe (laisser vide pour ne pas changer)',
                'mapped' => false,
                'required' => $options['is_new'],
                'constraints' => $options['is_new'] ? [
                    new NotBlank(message: 'Merci de saisir un mot de passe.'),
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ] : [],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false,
            'personnel_actuel' => null,
            'personnel_disponibles' => [],
        ]);
        $resolver->setAllowedTypes('personnel_actuel', ['null', Personnel::class]);
        $resolver->setAllowedTypes('personnel_disponibles', 'array');
    }
}
