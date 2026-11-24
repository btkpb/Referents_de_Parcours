<?php

namespace App\Controller;

use App\Entity\AffectationResponsableApprenti;
use App\Entity\Candidat;
use App\Entity\Emploi;
use App\Entity\EntretienParcours;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FollowupController extends AbstractController
{
    private const ROLES_SUIVI = [
        'ROLE_ADMIN', 'ROLE_ADMINISTRATEUR', 'ROLE_SUPER_ADMIN',
        'ROLE_GESTIONNAIRE', 'ROLE_REFERENT_PARCOURS', 'ROLE_REFERENT',
        'ROLE_FORMATEUR',
    ];

    /**
     * Retourne null pour les gestionnaires (accès global),
     * sinon les identifiants des bénéficiaires affectés à l'utilisateur.
     */
    private function candidatsAutorises(EntityManagerInterface $em): ?array
    {
        if ($this->isGranted('ROLE_GESTIONNAIRE')) {
            return null;
        }

        $types = [];

        if ($this->isGranted('ROLE_REFERENT_PARCOURS') || $this->isGranted('ROLE_REFERENT')) {
            $types[] = AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS;
        }

        if ($this->isGranted('ROLE_FORMATEUR')) {
            $types[] = AffectationResponsableApprenti::TYPE_FORMATEUR;
        }

        if ($types === [] || $this->getUser() === null) {
            return [];
        }

        $affectations = $em->getRepository(AffectationResponsableApprenti::class)->findBy([
            'responsable' => $this->getUser(),
            'typeResponsabilite' => $types,
            'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
            'dateFin' => null,
        ]);

        $ids = [];

        foreach ($affectations as $affectation) {
            if (!$affectation->estActive()) {
                continue;
            }

            $candidat = $affectation->getCandidat();

            if ($candidat !== null) {
                $ids[] = (int) $candidat->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    private function candidatEstAutorise(Candidat $candidat, ?array $idsAutorises): bool
    {
        return $idsAutorises === null
            || in_array((int) $candidat->getId(), $idsAutorises, true);
    }

    private function filtrerParBeneficiaire(array $elements, ?array $idsAutorises): array
    {
        if ($idsAutorises === null) {
            return $elements;
        }

        return array_values(array_filter(
            $elements,
            static function ($element) use ($idsAutorises): bool {
                $candidat = $element->getCandidat();

                return $candidat !== null
                    && in_array((int) $candidat->getId(), $idsAutorises, true);
            }
        ));
    }
    private function verifierAccesSuivi(): void
    {
        $autorise = false;
        foreach (self::ROLES_SUIVI as $role) {
            if ($this->isGranted($role)) { $autorise = true; break; }
        }
        if (!$autorise) {
            throw $this->createAccessDeniedException('Vous n’êtes pas autorisé à accéder aux dossiers de suivi.');
        }
    }

    #[Route('/suivi', name: 'followup_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->verifierAccesSuivi();

        $idsAutorises = $this->candidatsAutorises($em);
        $entretiens = $em->getRepository(EntretienParcours::class)->findBy([], ['dateEntretien' => 'DESC']);
        $emplois = $em->getRepository(Emploi::class)->findBy([], ['id' => 'DESC']);

        return $this->render('followup/index.html.twig', [
            'entretiens' => $this->filtrerParBeneficiaire($entretiens, $idsAutorises),
            'emplois' => $this->filtrerParBeneficiaire($emplois, $idsAutorises),
        ]);
    }

    #[Route('/suivi/nouveau', name: 'followup_new', methods: ['GET', 'POST'])]
    public function nouveau(Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierAccesSuivi();
        $entretien = new EntretienParcours();
        return $this->formulaire($request, $em, $entretien, false);
    }

    #[Route('/suivi/{id}/modifier', name: 'followup_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function modifier(EntretienParcours $entretien, Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierAccesSuivi();
        return $this->formulaire($request, $em, $entretien, true);
    }

    private function formulaire(Request $request, EntityManagerInterface $em, EntretienParcours $entretien, bool $edition): Response
    {
        $idsAutorises = $this->candidatsAutorises($em);

        if ($edition && $entretien->getCandidat() !== null
            && !$this->candidatEstAutorise($entretien->getCandidat(), $idsAutorises)) {
            throw $this->createAccessDeniedException('Cet entretien ne relève pas de vos bénéficiaires affectés.');
        }

        if ($edition && $entretien->getCandidat() === null && $idsAutorises !== null) {
            throw $this->createAccessDeniedException('Cet entretien ne possède aucun bénéficiaire autorisé.');
        }

        $candidats = $em->getRepository(Candidat::class)->findBy([], ['nom' => 'ASC', 'prenoms' => 'ASC']);

        if ($idsAutorises !== null) {
            $candidats = array_values(array_filter(
                $candidats,
                fn (Candidat $candidat): bool => $this->candidatEstAutorise($candidat, $idsAutorises)
            ));
        }
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('entretien_parcours', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton de sécurité invalide. Rechargez le formulaire.');
            }
            $type = (string) $request->request->get('typeEntretien', 'ACCUEIL');
            $typesAutorises = ['ACCUEIL', 'MI_PARCOURS', 'SORTIE'];
            if (!in_array($type, $typesAutorises, true)) {
                throw $this->createNotFoundException('Type d’entretien inconnu.');
            }
            $candidatId = filter_var($request->request->get('candidat_id'), FILTER_VALIDATE_INT);
            $candidat = $candidatId ? $em->getRepository(Candidat::class)->find($candidatId) : null;
            $date = (string) $request->request->get('dateEntretien', '');
            $details = $request->request->all('details');

            if ($candidat !== null && !$this->candidatEstAutorise($candidat, $idsAutorises)) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas enregistrer un suivi pour un bénéficiaire qui ne vous est pas affecté.');
            }

            if (!$candidat || $date === '') {
                $this->addFlash('danger', 'Veuillez sélectionner un bénéficiaire et renseigner la date du rendez-vous.');
            } else {
                try { $dateEntretien = new \DateTimeImmutable($date); }
                catch (\Throwable) { $dateEntretien = null; }
                if (!$dateEntretien) {
                    $this->addFlash('danger', 'La date du rendez-vous est invalide.');
                } else {
                    // Seuls les champs attendus sont conservés dans le JSON.
                    $clesAutorisees = [
                        'contact','lieuHabitation','numeroDaip','numeroAej','urgenceNom','urgenceContact',
                        'resume','situationFamiliale','mobilite','logement','sante','situationProfessionnelle',
                        'ressources','objectifs','freins','nomReferent','contactReferent','nomTuteur','contactTuteur',
                        'etablissementTheorique','etablissementStage','prochainRdv','actionsRealisees','pointsForts',
                        'pointsFaibles','freinsPedagogiques','avisFormateur','planAction','maintienFormation',
                        'commentaireBeneficiaire','commentaireTuteur','commentaireReferent','conclusionReferent',
                        'motifSortie','dateSortie','projet','abandon','contactBeneficiaire',
                    ];
                    $nettoyes = [];
                    foreach ($clesAutorisees as $cle) {
                        $valeur = $details[$cle] ?? '';
                        $nettoyes[$cle] = is_string($valeur) ? trim($valeur) : '';
                    }
                    $entretien->setCandidat($candidat)
                        ->setTypeEntretien($type)
                        ->setDateEntretien($dateEntretien)
                        ->setDetails($nettoyes)
                        ->setSynthese($nettoyes['resume'] ?: null)
                        ->setPlanAction($nettoyes['planAction'] ?: null)
                        ->setStatut('REALISE');
                    $em->persist($entretien);
                    $em->flush();
                    $this->addFlash('success', $edition ? 'Entretien de parcours mis à jour.' : 'Entretien de parcours enregistré.');
                    return $this->redirectToRoute('followup_index');
                }
            }
        }

        return $this->render('followup/form.html.twig', [
            'entretien' => $entretien,
            'candidats' => $candidats,
            'edition' => $edition,
            'details' => $entretien->getDetails(),
        ]);
    }

    #[Route('/suivi/repertoire', name: 'followup_directory', methods: ['GET'])]
    public function repertoire(EntityManagerInterface $em): Response
    {
        $this->verifierAccesSuivi();
        $idsAutorises = $this->candidatsAutorises($em);
        $entretiens = $em->getRepository(EntretienParcours::class)->findBy([], ['dateEntretien' => 'DESC']);
        $entretiens = $this->filtrerParBeneficiaire($entretiens, $idsAutorises);
        $comptages = [];
        foreach ($entretiens as $entretien) {
            $candidat = $entretien->getCandidat();
            if (!$candidat || !in_array($entretien->getTypeEntretien(), ['ACCUEIL', 'MI_PARCOURS'], true)) { continue; }
            $metier = $candidat->getMetier() ?: 'Métier non renseigné';
            $id = $candidat->getId();
            $comptages[$metier] ??= ['entree' => [], 'miParcours' => []];
            if ($entretien->getTypeEntretien() === 'ACCUEIL') { $comptages[$metier]['entree'][$id] = true; }
            if ($entretien->getTypeEntretien() === 'MI_PARCOURS') { $comptages[$metier]['miParcours'][$id] = true; }
        }
        $recapitulatif = [];
        foreach ($comptages as $metier => $comptage) {
            $recapitulatif[] = ['metier' => $metier, 'entree' => count($comptage['entree']), 'miParcours' => count($comptage['miParcours'])];
        }
        usort($recapitulatif, static fn (array $a, array $b): int => strcmp($a['metier'], $b['metier']));

        $lignes = [];
        foreach ($entretiens as $entretien) {
            if (!in_array($entretien->getTypeEntretien(), ['MI_PARCOURS', 'SORTIE'], true)) { continue; }
            $d = $entretien->getDetails();
            if (trim((string) ($d['freins'] ?? '')) === '' && trim((string) ($d['freinsPedagogiques'] ?? '')) === '' && trim((string) ($d['abandon'] ?? '')) === '') { continue; }
            $c = $entretien->getCandidat();
            $metier = $c?->getMetier() ?: 'Métier non renseigné';
            $lignes[] = [
                'referent' => trim(($d['nomReferent'] ?? '') . ' ' . ($d['contactReferent'] ?? '')),
                'metier' => $metier,
                'entreeCount' => count($comptages[$metier]['entree'] ?? []),
                'miCount' => count($comptages[$metier]['miParcours'] ?? []),
                'beneficiaire' => $c ? trim($c->getNom() . ' ' . $c->getPrenoms()) : 'Bénéficiaire supprimé',
                'freins' => trim(($d['freins'] ?? '') . ' ' . ($d['freinsPedagogiques'] ?? '')),
                'abandon' => $d['abandon'] ?? '',
                'contact' => (($d['contactBeneficiaire'] ?? '') ?: ($d['contact'] ?? $c?->getTelephone() ?? '')),
                'type' => $entretien->getTypeEntretien(),
                'date' => $entretien->getDateEntretien(),
            ];
        }
        return $this->render('followup/directory.html.twig', ['lignes' => $lignes, 'recapitulatif' => $recapitulatif]);
    }
}
