<?php

namespace App\Tests\Service;

use App\Entity\CarteProfessionnelle;
use App\Entity\DecisionConge;
use App\Entity\Delegation;
use App\Entity\Enum\RoleDelegable;
use App\Entity\Enum\StatutCarteProfessionnelle;
use App\Entity\DocumentAdministratif;
use App\Entity\ListeValeur;
use App\Entity\Personnel;
use App\Entity\User;
use App\Repository\CarteProfessionnelleRepository;
use App\Repository\DecisionCongeRepository;
use App\Repository\DelegationRepository;
use App\Repository\DocumentAdministratifRepository;
use App\Service\EcheanceRhService;
use PHPUnit\Framework\TestCase;

class EcheanceRhServiceTest extends TestCase
{
    private function personnel(string $nom): Personnel
    {
        $personnel = new Personnel();
        $personnel->setNom($nom);
        $personnel->setPrenom('Test');

        return $personnel;
    }

    private function service(
        array $documents = [],
        array $cartes = [],
        array $decisions = [],
        array $delegations = [],
    ): EcheanceRhService {
        $documentRepository = $this->createStub(DocumentAdministratifRepository::class);
        $documentRepository->method('findAvecExpiration')->willReturn($documents);

        $carteRepository = $this->createStub(CarteProfessionnelleRepository::class);
        $carteRepository->method('findBy')->willReturn($cartes);

        $decisionRepository = $this->createStub(DecisionCongeRepository::class);
        $decisionRepository->method('findAll')->willReturn($decisions);

        $delegationRepository = $this->createStub(DelegationRepository::class);
        $delegationRepository->method('findActives')->willReturn($delegations);

        return new EcheanceRhService($documentRepository, $carteRepository, $decisionRepository, $delegationRepository);
    }

    public function testDocumentExpireDansDixJoursClasseEnAVenir(): void
    {
        $document = new DocumentAdministratif();
        $document->setPersonnel($this->personnel('Diop'));
        $document->setType(new ListeValeur());
        $document->setLibelle('CNI');
        $document->setDateExpiration((new \DateTimeImmutable('today'))->modify('+10 days'));

        $resultat = $this->service(documents: [$document])->calculerDocuments();

        $this->assertCount(0, $resultat['enRetard']);
        $this->assertCount(1, $resultat['aVenir']);
        $this->assertSame('CNI', $resultat['aVenir'][0]['libelle']);
    }

    public function testDocumentExpireDepuisTroisJoursClasseEnRetard(): void
    {
        $document = new DocumentAdministratif();
        $document->setPersonnel($this->personnel('Diop'));
        $document->setType(new ListeValeur());
        $document->setLibelle('Contrat');
        $document->setDateExpiration((new \DateTimeImmutable('today'))->modify('-3 days'));

        $resultat = $this->service(documents: [$document])->calculerDocuments();

        $this->assertCount(1, $resultat['enRetard']);
        $this->assertCount(0, $resultat['aVenir']);
        $this->assertSame(3, $resultat['enRetard'][0]['jours']);
    }

    public function testDocumentLoinDeLEcheanceEstIgnore(): void
    {
        $document = new DocumentAdministratif();
        $document->setPersonnel($this->personnel('Diop'));
        $document->setType(new ListeValeur());
        $document->setLibelle('Diplôme');
        $document->setDateExpiration((new \DateTimeImmutable('today'))->modify('+1 year'));

        $resultat = $this->service(documents: [$document])->calculerDocuments();

        $this->assertCount(0, $resultat['enRetard']);
        $this->assertCount(0, $resultat['aVenir']);
    }

    public function testCarteExpirantSousSoixanteJoursEstSignalee(): void
    {
        $carte = new CarteProfessionnelle();
        $carte->setPersonnel($this->personnel('Fall'));
        $carte->setNumero('CP-001');
        $carte->setStatut(StatutCarteProfessionnelle::VALIDE);
        $carte->setDateDelivrance((new \DateTimeImmutable('today'))->modify('-5 years')->modify('+30 days'));

        $resultat = $this->service(cartes: [$carte])->calculerCartes();

        $this->assertCount(1, $resultat['aVenir']);
        $this->assertSame('CP-001', $resultat['aVenir'][0]['numero']);
    }

    public function testDecisionExpiranteEstSignalee(): void
    {
        $decision = new DecisionConge();
        $decision->setPersonnel($this->personnel('Ndiaye'));
        $decision->setNumeroDecision('DC-042');
        $decision->setDateExpiration((new \DateTimeImmutable('today'))->modify('+5 days'));

        $resultat = $this->service(decisions: [$decision])->calculerDecisions();

        $this->assertCount(1, $resultat['aVenir']);
        $this->assertSame('DC-042', $resultat['aVenir'][0]['numeroDecision']);
    }

    public function testDelegationExpirantSousQuinzeJoursEstSignalee(): void
    {
        $delegant = new User();
        $delegant->setNom('Sow');
        $delegant->setPrenom('Awa');

        $delegataire = new User();
        $delegataire->setNom('Ba');
        $delegataire->setPrenom('Moussa');

        $delegation = new Delegation();
        $delegation->setDelegant($delegant);
        $delegation->setDelegataire($delegataire);
        $delegation->setRoleDelegue(RoleDelegable::RH_CONGE);
        $delegation->setDateFin((new \DateTimeImmutable('today'))->modify('+5 days'));

        $resultat = $this->service(delegations: [$delegation])->calculerDelegations();

        $this->assertCount(1, $resultat['aVenir']);
        $this->assertSame('Awa Sow', $resultat['aVenir'][0]['delegant']);
        $this->assertSame('Moussa Ba', $resultat['aVenir'][0]['delegataire']);
    }

    public function testResultatsTriesParUrgenceCroissante(): void
    {
        $lointain = new DocumentAdministratif();
        $lointain->setPersonnel($this->personnel('Loin'));
        $lointain->setType(new ListeValeur());
        $lointain->setLibelle('Loin');
        $lointain->setDateExpiration((new \DateTimeImmutable('today'))->modify('+25 days'));

        $proche = new DocumentAdministratif();
        $proche->setPersonnel($this->personnel('Proche'));
        $proche->setType(new ListeValeur());
        $proche->setLibelle('Proche');
        $proche->setDateExpiration((new \DateTimeImmutable('today'))->modify('+2 days'));

        $resultat = $this->service(documents: [$lointain, $proche])->calculerDocuments();

        $this->assertSame('Proche', $resultat['aVenir'][0]['libelle']);
        $this->assertSame('Loin', $resultat['aVenir'][1]['libelle']);
    }
}
