<?php

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Entity\Enum\CategorieListeValeur;
use App\Entity\ListeValeur;
use App\Entity\MaterielInformatique;
use App\Entity\Personnel;
use App\Entity\Vehicule;
use App\Form\ListeValeurType;
use App\Repository\ListeValeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CRUD générique pour les listes de valeurs paramétrables (types, états...),
 * partagé par toutes les catégories de App\Entity\Enum\CategorieListeValeur.
 */
#[Route('/admin/listes', name: 'admin_liste_valeur_')]
#[IsGranted('ROLE_SUPERADMIN')]
class ListeValeurController extends AbstractController
{
    #[Route('/{categorie}', name: 'index', methods: ['GET'])]
    public function index(CategorieListeValeur $categorie, ListeValeurRepository $repository): Response
    {
        return $this->render('admin/liste_valeur/index.html.twig', [
            'categorie' => $categorie,
            'valeurs' => $repository->findByCategorie($categorie),
        ]);
    }

    #[Route('/{categorie}/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(CategorieListeValeur $categorie, Request $request, EntityManagerInterface $em): Response
    {
        $valeur = new ListeValeur();
        $valeur->setCategorie($categorie);
        $form = $this->createForm(ListeValeurType::class, $valeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($valeur);
            $em->flush();

            $this->addFlash('success', 'Valeur ajoutée.');

            return $this->redirectToRoute('admin_liste_valeur_index', ['categorie' => $categorie->value]);
        }

        return $this->render('admin/liste_valeur/new.html.twig', [
            'categorie' => $categorie,
            'valeur' => $valeur,
            'form' => $form,
        ]);
    }

    #[Route('/{categorie}/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(CategorieListeValeur $categorie, ListeValeur $valeur, Request $request, EntityManagerInterface $em): Response
    {
        if ($valeur->getCategorie() !== $categorie) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ListeValeurType::class, $valeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Valeur mise à jour.');

            return $this->redirectToRoute('admin_liste_valeur_index', ['categorie' => $categorie->value]);
        }

        return $this->render('admin/liste_valeur/edit.html.twig', [
            'categorie' => $categorie,
            'valeur' => $valeur,
            'form' => $form,
        ]);
    }

    #[Route('/{categorie}/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CategorieListeValeur $categorie, ListeValeur $valeur, Request $request, EntityManagerInterface $em): Response
    {
        if ($valeur->getCategorie() !== $categorie) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete-liste-valeur-'.$valeur->getId(), $request->request->get('_token'))) {
            if ($this->isUsed($valeur, $em)) {
                $this->addFlash('danger', 'Impossible de supprimer cette valeur : elle est encore utilisée par des enregistrements.');
            } else {
                $em->remove($valeur);
                $em->flush();
                $this->addFlash('success', 'Valeur supprimée.');
            }
        }

        return $this->redirectToRoute('admin_liste_valeur_index', ['categorie' => $categorie->value]);
    }

    private function isUsed(ListeValeur $valeur, EntityManagerInterface $em): bool
    {
        [$class, $field] = match ($valeur->getCategorie()) {
            CategorieListeValeur::TYPE_MATERIEL => [MaterielInformatique::class, 'type'],
            CategorieListeValeur::ETAT_MATERIEL => [MaterielInformatique::class, 'etat'],
            CategorieListeValeur::TYPE_VEHICULE => [Vehicule::class, 'type'],
            CategorieListeValeur::ETAT_VEHICULE => [Vehicule::class, 'etat'],
            CategorieListeValeur::TYPE_CONTRAT => [Personnel::class, 'typeContrat'],
        };

        $count = $em->createQuery("SELECT COUNT(e.id) FROM {$class} e WHERE e.{$field} = :lv")
            ->setParameter('lv', $valeur)
            ->getSingleScalarResult();

        return $count > 0;
    }
}
