<?php

namespace App\Form;

use App\Entity\Direction;
use App\Entity\Personnel;
use App\Entity\Service;
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

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('direction', EntityType::class, [
                'label' => 'Direction de rattachement',
                'class' => Direction::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner...',
            ])
            ->add('code', TextType::class, [
                'label' => 'Code',
                'attr' => ['placeholder' => 'ex: DSI, DRH, DIRCOM'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom du service / direction',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Service actif',
                'required' => false,
            ])
            ->add('noteServiceNumero', TextType::class, [
                'label' => 'N° note de service',
                'required' => false,
                'help' => 'Obligatoire dès qu\'un responsable est désigné (voir Service::validerNoteService()).',
            ])
            ->add('noteServiceDate', DateType::class, [
                'label' => 'Date de la note de service',
                'required' => false,
                'widget' => 'single_text',
            ])
            // Non-mappé : le champ entité $noteServiceFichier stocke un chemin, pas
            // un UploadedFile — ServiceController::gererNoteServiceFichier() s'occupe
            // du stockage, même principe que UserType::personnel (voir son commentaire).
            ->add('noteServiceFichierUpload', FileType::class, [
                'label' => 'Note de service scannée (facultatif)',
                'required' => false,
                'mapped' => false,
                'constraints' => [new File(maxSize: '10M', mimeTypes: ['application/pdf', 'image/jpeg', 'image/png'])],
            ])
        ;

        // Champ 'responsable' ajouté via PRE_SET_DATA plutôt que dans buildForm()
        // directement : le query_builder doit filtrer sur CE service précis (déjà
        // affecté, voir Personnel::$service), connu seulement une fois les
        // données du formulaire posées — impossible à ce stade avec un simple
        // 'class' => Personnel::class comme avant, qui listait les ~200 agents du
        // Ministère entier.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $service = $event->getData();

            $event->getForm()->add('responsable', EntityType::class, [
                'label' => 'Responsable / chef de service',
                'class' => Personnel::class,
                'choice_label' => 'nomComplet',
                'placeholder' => 'Aucun',
                'required' => false,
                'query_builder' => static function (EntityRepository $er) use ($service) {
                    $qb = $er->createQueryBuilder('p')->orderBy('p.nom', 'ASC');

                    // Service pas encore créé (formulaire de création) : aucun
                    // agent n'y est encore affecté, la liste reste vide.
                    return $service instanceof Service && $service->getId()
                        ? $qb->andWhere('p.service = :service')->setParameter('service', $service)
                        : $qb->andWhere('1 = 0');
                },
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
