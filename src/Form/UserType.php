<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de gestion des comptes agents (accès à l'application).
 * Le mot de passe n'est requis qu'à la création (voir option 'is_new').
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, ['label' => 'Email de connexion'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Agent' => User::ROLE_AGENT,
                    'Administrateur' => User::ROLE_ADMIN,
                    'Super administrateur' => User::ROLE_SUPERADMIN,
                ],
                'multiple' => true,
                'expanded' => true,
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
        ]);
    }
}
