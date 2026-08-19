<?php

namespace App\Form;

use App\Entity\Direction;
use App\Entity\Personnel;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DirectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => ['placeholder' => 'ex: DSI, DRH, DIRCOM'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de la direction',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Direction active',
                'required' => false,
            ])
            ->add('noteServiceNumero', TextType::class, [
                'label' => 'N° note de service',
                'required' => false,
                'help' => 'Obligatoire dès qu\'un directeur est désigné (voir Direction::validerNoteService()).',
            ])
            ->add('noteServiceDate', DateType::class, [
                'label' => 'Date de la note de service',
                'required' => false,
                'widget' => 'single_text',
            ])
            // Non-mappé : voir ServiceType::noteServiceFichierUpload pour la raison.
            ->add('noteServiceFichierUpload', FileType::class, [
                'label' => 'Note de service scannée (facultatif)',
                'required' => false,
                'mapped' => false,
                'constraints' => [new File(maxSize: '10M', mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'])],
            ])
        ;

        // Champ 'directeur' ajouté via PRE_SET_DATA plutôt que dans buildForm()
        // directement : le query_builder doit filtrer sur les agents des services
        // de CETTE direction précise (via Personnel::$service->direction), connu
        // seulement une fois les données du formulaire posées — voir ServiceType
        // pour le même besoin côté responsable de service.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $direction = $event->getData();

            $event->getForm()->add('directeur', EntityType::class, [
                'label' => 'Directeur',
                'class' => Personnel::class,
                'choice_label' => 'nomComplet',
                'placeholder' => 'Aucun',
                'required' => false,
                'query_builder' => static function (EntityRepository $er) use ($direction) {
                    $qb = $er->createQueryBuilder('p')
                        ->leftJoin('p.service', 's')
                        ->orderBy('p.nom', 'ASC');

                    // Direction pas encore créée (formulaire de création) :
                    // aucun service, donc aucun agent, ne s'y rattache encore.
                    return $direction instanceof Direction && $direction->getId()
                        ? $qb->andWhere('s.direction = :direction')->setParameter('direction', $direction)
                        : $qb->andWhere('1 = 0');
                },
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Direction::class,
        ]);
    }
}
