<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Personnel;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\PersonnelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestion des comptes agents (connexion à l'application GERM).
 */
#[Route('/admin/agent', name: 'admin_agent_')]
#[IsGranted('ROLE_SUPERADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $repository): Response
    {
        return $this->render('admin/agent/index.html.twig', [
            'agents' => $repository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, PersonnelRepository $personnelRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_new' => true,
            'personnel_disponibles' => $personnelRepository->findDisponiblesPourCompte(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->lierPersonnelOuRefuser($form, $user)) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte agent créé.');

            return $this->redirectToRoute('admin_agent_index');
        }

        return $this->render('admin/agent/new.html.twig', [
            'agent' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, PersonnelRepository $personnelRepository): Response
    {
        $personnelActuel = $personnelRepository->findOneBy(['user' => $user]);
        $form = $this->createForm(UserType::class, $user, [
            'is_new' => false,
            'personnel_actuel' => $personnelActuel,
            'personnel_disponibles' => $personnelRepository->findDisponiblesPourCompte($personnelActuel),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->lierPersonnelOuRefuser($form, $user, $personnelActuel)) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }
            $em->flush();

            $this->addFlash('success', 'Compte agent mis à jour.');

            return $this->redirectToRoute('admin_agent_index');
        }

        return $this->render('admin/agent/edit.html.twig', [
            'agent' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Applique la règle métier "fiche agent obligatoire, sauf pour un compte
     * super administrateur" et synchronise la relation bidirectionnelle
     * (portée par Personnel::$user, jamais mappée automatiquement par le
     * formulaire côté User). Retourne false — et attache une erreur au champ
     * — si la sélection est invalide ; le formulaire est alors ré-affiché.
     */
    private function lierPersonnelOuRefuser(FormInterface $form, User $user, ?Personnel $personnelActuel = null): bool
    {
        $rolesSelectionnes = $form->get('roles')->getData();
        $estSuperAdmin = in_array(User::ROLE_SUPERADMIN, $rolesSelectionnes, true);
        /** @var Personnel|null $personnelSelectionne */
        $personnelSelectionne = $form->get('personnel')->getData();

        if (!$estSuperAdmin && !$personnelSelectionne) {
            $form->get('personnel')->addError(new FormError('Merci de sélectionner la fiche agent liée à ce compte (obligatoire, sauf pour un compte super administrateur).'));

            return false;
        }

        if ($personnelActuel && $personnelActuel !== $personnelSelectionne) {
            $personnelActuel->setUser(null);
        }
        if ($personnelSelectionne) {
            $personnelSelectionne->setUser($user);
        }

        return true;
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-agent-'.$user->getId(), $request->request->get('_token'))) {
            if (!$user->getDelegationsRecues()->isEmpty() || !$user->getDelegationsAccordees()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer ce compte : il est impliqué dans une ou plusieurs délégations de rôle.');
            } else {
                $em->remove($user);
                $em->flush();
                $this->addFlash('success', 'Compte agent supprimé.');
            }
        }

        return $this->redirectToRoute('admin_agent_index');
    }
}
