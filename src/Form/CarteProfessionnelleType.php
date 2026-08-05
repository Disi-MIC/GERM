<?php

namespace App\Form;

use App\Entity\CarteProfessionnelle;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\Personnel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Le champ 'personnel' n'y figure que si l'option 'include_personnel' est activée
 * (ajout depuis la page ministérielle) ; sinon il est fixé par le contrôleur depuis
 * l'agent de la route.
 */
class CarteProfessionnelleType extends AbstractType
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
            ->add('numero', TextType::class, ['label' => 'Numéro de carte'])
            ->add('dateDelivrance', DateType::class, [
                'label' => 'Date de délivrance',
                'widget' => 'single_text',
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => "Date d'expiration",
                'widget' => 'single_text',
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'class' => StatutCarteProfessionnelle::class,
                'choice_label' => fn (StatutCarteProfessionnelle $s) => $s->label(),
            ])
            ->add('observations', TextareaType::class, ['label' => 'Observations', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('fichier', FileType::class, [
                'label' => 'Scan / photo de la carte',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Assert\File(maxSize: '5M', mimeTypes: self::MIME_TYPES, mimeTypesMessage: self::MIME_TYPES_MESSAGE)],
            ])
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
