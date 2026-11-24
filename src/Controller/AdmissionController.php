<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Admission;
use App\Entity\Candidat;
use App\Entity\Entretien;
use App\Entity\Evaluation;
use App\Entity\QuotaMetier;
use App\Entity\User;
use App\Entity\VisiteMedicale;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdmissionController extends AbstractController
{
    #[Route('/recrutement/admissions', name: 'recruitment_admissions', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $user = $this->getUser();

        if (!$user instanceof User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte Gestionnaire est inactif.'
            );
        }

        $entretiens = $em->getRepository(Entretien::class)
            ->findBy([], ['dateHeure' => 'DESC', 'id' => 'DESC']);

        $dossiers = [];
        $candidatsTraites = [];

        foreach ($entretiens as $entretien) {
            $candidat = $entretien->getCandidat();

            if (!$candidat instanceof Candidat || $candidat->getId() === null) {
                continue;
            }

            if (isset($candidatsTraites[$candidat->getId()])) {
                continue;
            }

            $candidatsTraites[$candidat->getId()] = true;

            $analyse = $this->analyserEntretien($em, $entretien);

            $admission = $em->getRepository(Admission::class)
                ->findOneBy(['candidat' => $candidat]);

            $dossiers[] = [
                'candidat' => $candidat,
                'entretien' => $entretien,
                'analyse' => $analyse,
                'admission' => $admission,
            ];
        }

        return $this->render('recrutement/admissions.html.twig', [
            'dossiers' => $dossiers,
            'seuilAdmission' => 24,
        ]);
    }

    #[Route(
        '/recrutement/admissions/{id}/traiter',
        name: 'recruitment_admission_traiter',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function traiter(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $user = $this->getUser();

        if (!$user instanceof User || !$user->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte Gestionnaire est inactif.'
            );
        }

        $candidat = $em->getRepository(Candidat::class)->find($id);

        if (!$candidat instanceof Candidat) {
            throw $this->createNotFoundException('Candidat introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'admission_decision_' . $id,
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Le formulaire a expiré. Veuillez réessayer.');

            return $this->redirectToRoute('recruitment_admissions');
        }

        $entretiens = $em->getRepository(Entretien::class)->findBy(
            ['candidat' => $candidat],
            ['dateHeure' => 'DESC', 'id' => 'DESC'],
            1
        );

        if ($entretiens === []) {
            $this->addFlash('danger', 'Aucun entretien trouvé pour ce candidat.');

            return $this->redirectToRoute('recruitment_admissions');
        }

        $analyse = $this->analyserEntretien($em, $entretiens[0]);

        if (!$analyse['complet']) {
            $this->addFlash(
                'warning',
                'Décision impossible : toutes les évaluations des jurys doivent être terminées.'
            );

            return $this->redirectToRoute('recruitment_admissions');
        }

        $admission = $em->getRepository(Admission::class)
            ->findOneBy(['candidat' => $candidat]);

        if (!$admission instanceof Admission) {
            $admission = new Admission();
            $admission->setCandidat($candidat);
            $em->persist($admission);
        }

        $admission
            ->setScoreMoyen($analyse['scoreMoyen'])
            ->setAvisJury($analyse['avis']);

        $action = (string) $request->request->get('decision');
        $decisionFinale = 'EN_ATTENTE';

        if ($analyse['avis'] === 'CONTRADICTOIRE') {
            if (!in_array($action, [
                'ARBITRAGE_FAVORABLE',
                'ARBITRAGE_DEFAVORABLE',
            ], true)) {
                $this->addFlash(
                    'warning',
                    'Les avis des jurys sont contradictoires. Un arbitrage est nécessaire.'
                );

                return $this->redirectToRoute('recruitment_admissions');
            }

            $arbitrage = $action === 'ARBITRAGE_FAVORABLE'
                ? 'FAVORABLE'
                : 'DEFAVORABLE';

            $admission
                ->setArbitrageGestionnaire($arbitrage)
                ->setObservationsArbitrage(
                    trim((string) $request->request->get('observations'))
                )
                ->setDateArbitrage(new \DateTime())
                ->setArbitre($user);

            if ($arbitrage === 'DEFAVORABLE') {
                $decisionFinale = 'REFUSE';
            } else {
                $decisionFinale = $this->verifierConditionsAdmission($analyse);
            }
        } elseif ($analyse['avis'] === 'FAVORABLE') {
            if ($action !== 'TRAITER') {
                $this->addFlash('warning', 'Action de validation non reconnue.');

                return $this->redirectToRoute('recruitment_admissions');
            }

            $decisionFinale = $this->verifierConditionsAdmission($analyse);
        } elseif ($analyse['avis'] === 'DEFAVORABLE') {
            if ($action !== 'TRAITER') {
                $this->addFlash('warning', 'Action de traitement non reconnue.');

                return $this->redirectToRoute('recruitment_admissions');
            }

            $decisionFinale = 'REFUSE';
        }

        $admission->setDecision($decisionFinale);

        if ($decisionFinale === 'ADMIS') {
            $admission
                ->setMetierAffecte($candidat->getMetier())
                ->setDateAdmission(new \DateTime())
                ->setCodeAdmission(sprintf(
                    'E2C-%s-%06d',
                    date('Y'),
                    $candidat->getId()
                ));

            $this->addFlash('success', 'Admission validée avec succès.');
        } elseif ($decisionFinale === 'REFUSE') {
            $admission
                ->setDateAdmission(null)
                ->setCodeAdmission(null);

            $this->addFlash('info', 'La candidature a été déclarée non admise.');
        } else {
            $admission
                ->setDateAdmission(null)
                ->setCodeAdmission(null);

            $this->addFlash(
                'warning',
                'Admission suspendue : la note minimale, l’aptitude médicale ou le quota ne permet pas encore de valider ce dossier.'
            );
        }

        $em->flush();

        return $this->redirectToRoute('recruitment_admissions');
    }

    private function analyserEntretien(
        EntityManagerInterface $em,
        Entretien $entretien
    ): array {
        $candidat = $entretien->getCandidat();
        $jurys = $entretien->getJurys()->toArray();

        $evaluations = $em->getRepository(Evaluation::class)
            ->findBy(['entretien' => $entretien]);

        $evaluationsParJury = [];

        foreach ($evaluations as $evaluation) {
            $jury = $evaluation->getJury();

            if ($jury !== null && $jury->getId() !== null) {
                $evaluationsParJury[$jury->getId()] = $evaluation;
            }
        }

        $complet = count($jurys) > 0
            && count($evaluationsParJury) === count($jurys);

        $votes = [];
        $notes = [];

        foreach ($jurys as $jury) {
            $juryId = $jury->getId();

            if ($juryId === null || !isset($evaluationsParJury[$juryId])) {
                $complet = false;
                continue;
            }

            $evaluation = $evaluationsParJury[$juryId];
            $vote = $evaluation->getDecision();

            if (!in_array($vote, ['FAVORABLE', 'DEFAVORABLE'], true)) {
                $complet = false;
                continue;
            }

            $votes[] = $vote;
            $notes[] = $evaluation->total();
        }

        $scoreMoyen = $notes !== []
            ? round(array_sum($notes) / count($notes), 2)
            : null;

        $avis = 'EN_ATTENTE';

        if ($complet && count($votes) === count($jurys)) {
            $avis = count(array_unique($votes)) === 1
                ? $votes[0]
                : 'CONTRADICTOIRE';
        }

        $visites = $candidat instanceof Candidat
            ? $em->getRepository(VisiteMedicale::class)->findBy(
                ['candidat' => $candidat],
                ['dateHeure' => 'DESC', 'id' => 'DESC'],
                1
            )
            : [];

        $derniereVisite = $visites[0] ?? null;

        $quota = $candidat instanceof Candidat
            ? $this->informationsQuota($em, $candidat)
            : [
                'quota' => 0,
                'admis' => 0,
                'disponible' => false,
                'catalogue' => null,
            ];

        return [
            'complet' => $complet,
            'nombreJurys' => count($jurys),
            'nombreEvaluations' => count($evaluationsParJury),
            'avis' => $avis,
            'scoreMoyen' => $scoreMoyen,
            'derniereVisite' => $derniereVisite,
            'aptitude' => $derniereVisite?->getAptitude() ?? 'NON_EFFECTUEE',
            'quota' => $quota,
        ];
    }

    private function verifierConditionsAdmission(array $analyse): string
    {
        if ($analyse['scoreMoyen'] === null || $analyse['scoreMoyen'] < 24) {
            return 'EN_ATTENTE';
        }

        if ($analyse['aptitude'] !== 'APTE') {
            return 'EN_ATTENTE';
        }

        if (!$analyse['quota']['disponible']) {
            return 'EN_ATTENTE';
        }

        return 'ADMIS';
    }

    private function informationsQuota(
        EntityManagerInterface $em,
        Candidat $candidat
    ): array {
        $campagne = $candidat->getCampagne();
        $metier = $this->normaliserMetier($candidat->getMetier());

        if ($campagne === null || $metier === '') {
            return [
                'quota' => 0,
                'admis' => 0,
                'disponible' => false,
                'catalogue' => null,
            ];
        }

        $quotas = $em->getRepository(QuotaMetier::class)
            ->findBy(['campagne' => $campagne]);

        $quotaTrouve = null;

        foreach ($quotas as $quota) {
            $nomCatalogue = $quota->getMetier()?->getNom();

            if ($nomCatalogue !== null
                && $this->normaliserMetier($nomCatalogue) === $metier
            ) {
                $quotaTrouve = $quota;
                break;
            }
        }

        if (!$quotaTrouve instanceof QuotaMetier) {
            return [
                'quota' => 0,
                'admis' => 0,
                'disponible' => false,
                'catalogue' => null,
            ];
        }

        $admissions = $em->getRepository(Admission::class)
            ->findBy(['decision' => 'ADMIS']);

        $nombreAdmis = 0;

        foreach ($admissions as $admissionExistante) {
            $autreCandidat = $admissionExistante->getCandidat();

            if ($autreCandidat === null) {
                continue;
            }

            if ($autreCandidat->getCampagne()?->getId() === $campagne->getId()
                && $this->normaliserMetier($autreCandidat->getMetier()) === $metier
            ) {
                $nombreAdmis++;
            }
        }

        $limite = $quotaTrouve->getQuota();

        $admissionActuelle = $em->getRepository(Admission::class)
            ->findOneBy(['candidat' => $candidat]);

        $dejaAdmis = $admissionActuelle instanceof Admission
            && $admissionActuelle->getDecision() === 'ADMIS';

        return [
            'quota' => $limite,
            'admis' => $nombreAdmis,
            'disponible' => $dejaAdmis
                ? $nombreAdmis <= $limite
                : $nombreAdmis < $limite,
            'catalogue' => $quotaTrouve->getMetier()?->getNom(),
        ];
    }

    private function normaliserMetier(string $metier): string
    {
        $metier = mb_strtolower(trim($metier), 'UTF-8');

        $translittere = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $metier);

        if ($translittere !== false) {
            $metier = $translittere;
        }

        return preg_replace('/[^a-z0-9]/', '', $metier) ?? '';
    }
}