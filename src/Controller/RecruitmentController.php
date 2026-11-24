<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Campagne;
use App\Entity\Candidat;
use App\Entity\Entretien;
use App\Entity\Evaluation;
use App\Entity\User;
use App\Service\CandidatService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecruitmentController extends AbstractController
{
    #[Route(
        '/recrutement/entretiens/planifier',
        name: 'recruitment_planifier',
        methods: ['GET', 'POST']
    )]
    public function planifier(
        Request $r,
        EntityManagerInterface $em,
        CandidatService $candidatService
    ): Response {
        $gestionnaire = $this->getUser();

        if (
            !$gestionnaire instanceof User
            || !$gestionnaire->isActive()
            || !$this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            throw $this->createAccessDeniedException(
                'Seul un gestionnaire actif peut planifier les entretiens.'
            );
        }

        $campagnes = $em->getRepository(Campagne::class)->findBy(
            ['active' => true],
            ['dateDebut' => 'DESC']
        );

        $campagneId = $r->isMethod('POST')
            ? $r->request->get('campagne_id')
            : $r->query->get('campagne');

        $campagne = null;

        if (
            $campagneId !== null
            && ctype_digit((string) $campagneId)
        ) {
            $campagne = $em->getRepository(Campagne::class)
                ->find((int) $campagneId);
        }

        if (!$campagne instanceof Campagne || !$campagne->isActive()) {
            $campagne = null;
        }

        $candidatsEligibles = [];

        if ($campagne instanceof Campagne) {
            $ageMin = $campagne->getAgeMin() ?? 12;
            $ageMax = $campagne->getAgeMax() ?? 35;

            $candidats = $em->getRepository(Candidat::class)->findBy(
                ['campagne' => $campagne],
                ['nom' => 'ASC', 'prenoms' => 'ASC']
            );

            foreach ($candidats as $candidat) {
                $resultat = $candidatService->estEligible(
                    $candidat,
                    $ageMin,
                    $ageMax
                );

                if ($resultat['eligible'] === true) {
                    $candidatsEligibles[] = $candidat;
                }
            }
        }

        $utilisateurs = $em->getRepository(User::class)->findAll();

        $jurys = array_values(array_filter(
            $utilisateurs,
            static fn (User $u): bool =>
                $u->isActive()
                && in_array('ROLE_JURY', $u->getRoles(), true)
        ));

        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'planifier_entretien',
                (string) $r->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Le formulaire a expiré. Veuillez le recharger.'
                );
            }

            try {
                if (!$campagne instanceof Campagne) {
                    throw new \InvalidArgumentException(
                        'Veuillez sélectionner une campagne active.'
                    );
                }

                $candidatId = $r->request->get('candidat_id');

                if (
                    !is_scalar($candidatId)
                    || !ctype_digit((string) $candidatId)
                ) {
                    throw new \InvalidArgumentException(
                        'Veuillez sélectionner un candidat valide.'
                    );
                }

                $candidat = $em->getRepository(Candidat::class)
                    ->find((int) $candidatId);

                if (
                    !$candidat instanceof Candidat
                    || $candidat->getCampagne()?->getId() !== $campagne->getId()
                ) {
                    throw new \InvalidArgumentException(
                        'Le candidat ne correspond pas à cette campagne.'
                    );
                }

                $resultat = $candidatService->estEligible(
                    $candidat,
                    $campagne->getAgeMin() ?? 12,
                    $campagne->getAgeMax() ?? 35
                );

                if (!$resultat['eligible']) {
                    throw new \InvalidArgumentException(
                        'Ce candidat ne satisfait pas aux critères d’admissibilité.'
                    );
                }

                $entretienExistant = $em->getRepository(Entretien::class)
                    ->findOneBy([
                        'candidat' => $candidat,
                        'statut' => 'PLANIFIE',
                    ]);

                if ($entretienExistant instanceof Entretien) {
                    throw new \InvalidArgumentException(
                        'Ce candidat possède déjà un entretien planifié.'
                    );
                }

                $dateBrute = trim((string) $r->request->get('dateHeure'));

                $dateHeure = \DateTimeImmutable::createFromFormat(
                    '!Y-m-d\TH:i',
                    $dateBrute
                );

                $erreursDate = \DateTimeImmutable::getLastErrors();

                if (
                    !$dateHeure
                    || (
                        $erreursDate !== false
                        && (
                            $erreursDate['warning_count'] > 0
                            || $erreursDate['error_count'] > 0
                        )
                    )
                ) {
                    throw new \InvalidArgumentException(
                        'Veuillez saisir une date et une heure valides.'
                    );
                }

                if ($dateHeure <= new \DateTimeImmutable()) {
                    throw new \InvalidArgumentException(
                        'La date de l’entretien doit être dans le futur.'
                    );
                }

                $lieu = trim((string) $r->request->get('lieu'));

                if ($lieu === '' || mb_strlen($lieu, 'UTF-8') > 180) {
                    throw new \InvalidArgumentException(
                        'Le lieu est obligatoire et ne doit pas dépasser 180 caractères.'
                    );
                }

                $juryIds = $r->request->all('jurys');

                if ($juryIds === []) {
                    throw new \InvalidArgumentException(
                        'Veuillez affecter au moins un jury.'
                    );
                }

                $juryIds = array_unique($juryIds);
                $jurysValides = [];

                foreach ($juryIds as $juryId) {
                    if (!is_scalar($juryId) || !ctype_digit((string) $juryId)) {
                        throw new \InvalidArgumentException(
                            'La sélection des jurys est invalide.'
                        );
                    }

                    $jury = $em->getRepository(User::class)
                        ->find((int) $juryId);

                    if (
                        !$jury instanceof User
                        || !$jury->isActive()
                        || !in_array('ROLE_JURY', $jury->getRoles(), true)
                    ) {
                        throw new \InvalidArgumentException(
                            'Un des jurys sélectionnés est inactif ou ne possède pas le rôle Jury.'
                        );
                    }

                    $jurysValides[] = $jury;
                }

                $entretien = new Entretien();
                $entretien
                    ->setCandidat($candidat)
                    ->setDateHeure(\DateTime::createFromImmutable($dateHeure))
                    ->setLieu($lieu)
                    ->setStatut('PLANIFIE');

                foreach ($jurysValides as $jury) {
                    $entretien->addJury($jury);
                }

                $em->persist($entretien);
                $em->flush();

                $this->addFlash('success', 'Entretien planifié avec succès !');

                return $this->redirectToRoute('recruitment_interviews');
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('recrutement/planifier.html.twig', [
            'campagnes' => $campagnes,
            'campagneSelectionnee' => $campagne,
            'candidatsEligibles' => $candidatsEligibles,
            'jurys' => $jurys,
        ]);
    }

    #[Route(
        '/recrutement/entretiens',
        name: 'recruitment_interviews',
        methods: ['GET']
    )]
    public function interviews(
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User || !$utilisateur->isActive()) {
            throw $this->createAccessDeniedException(
                'Votre compte ne permet pas de consulter les entretiens.'
            );
        }

        $estGestionnaire = $this->isGranted('ROLE_GESTIONNAIRE');

        if (!$estGestionnaire && !$this->isGranted('ROLE_JURY')) {
            throw $this->createAccessDeniedException();
        }

        $recherche = trim((string) $request->query->get('q', ''));
        $statut = trim((string) $request->query->get('statut', ''));

        if (!in_array($statut, ['', 'PLANIFIE'], true)) {
            $statut = '';
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limite = 10;

        $repository = $em->getRepository(Entretien::class);

        $qb = $repository->createQueryBuilder('e')
            ->leftJoin('e.candidat', 'c')
            ->addSelect('c')
            ->orderBy('e.dateHeure', 'ASC');

        if (!$estGestionnaire) {
            $qb->innerJoin('e.jurys', 'j')
                ->andWhere('j = :jury')
                ->setParameter('jury', $utilisateur);
        }

        if ($recherche !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(c.nom) LIKE :recherche',
                    'LOWER(c.prenoms) LIKE :recherche',
                    'LOWER(c.numero) LIKE :recherche',
                    'LOWER(e.lieu) LIKE :recherche',
                    'LOWER(e.juryNoms) LIKE :recherche'
                )
            )->setParameter(
                'recherche',
                '%' . mb_strtolower($recherche) . '%'
            );
        }

        if ($statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
        $total = count($paginator);
        $nombrePages = max(1, (int) ceil($total / $limite));

        if ($page > $nombrePages) {
            $page = $nombrePages;
        }

        $qb->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
        $items = iterator_to_array($paginator);

        $idsEntretiens = array_values(array_filter(array_map(
            static fn (Entretien $entretien): ?int => $entretien->getId(),
            $items
        )));

        $evaluationsIds = [];
        $entretiensAvecEvaluationsIds = [];

        if ($idsEntretiens !== []) {
            $evaluations = $em->createQueryBuilder()
                ->select('IDENTITY(ev.entretien) AS entretienId')
                ->addSelect('IDENTITY(ev.jury) AS juryId')
                ->from(Evaluation::class, 'ev')
                ->where('ev.entretien IN (:entretiens)')
                ->setParameter('entretiens', $idsEntretiens)
                ->getQuery()
                ->getArrayResult();

            foreach ($evaluations as $evaluation) {
                $entretienId = (int) $evaluation['entretienId'];
                $juryId = (int) $evaluation['juryId'];

                $entretiensAvecEvaluationsIds[$entretienId] = true;

                if (
                    !$estGestionnaire
                    && $juryId === $utilisateur->getId()
                ) {
                    $evaluationsIds[$entretienId] = true;
                }
            }
        }

        $statistiquesQb = $repository->createQueryBuilder('e')
            ->select('COUNT(e.id) AS total')
            ->addSelect(
                "SUM(CASE WHEN e.statut = 'PLANIFIE' THEN 1 ELSE 0 END) AS planifies"
            );

        if (!$estGestionnaire) {
            $statistiquesQb
                ->innerJoin('e.jurys', 'j')
                ->andWhere('j = :jury')
                ->setParameter('jury', $utilisateur);
        }

        $statistiques = $statistiquesQb->getQuery()->getSingleResult();

        return $this->render('recrutement/entretiens.html.twig', [
            'items' => $items,
            'juryConnecte' => $utilisateur,
            'evaluationsIds' => $evaluationsIds,
            'entretiensAvecEvaluationsIds' => $entretiensAvecEvaluationsIds,
            'estGestionnaire' => $estGestionnaire,
            'recherche' => $recherche,
            'statutFiltre' => $statut,
            'page' => $page,
            'nombrePages' => $nombrePages,
            'total' => $total,
            'limite' => $limite,
            'statistiques' => $statistiques,
        ]);
    }
    #[Route(
        '/recrutement/entretiens/{id}/evaluation',
        name: 'recruitment_eval',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function eval(
        Entretien $e,
        Request $r,
        EntityManagerInterface $em
    ): Response {
        $jury = $this->getUser();

        if (
            !$jury instanceof User
            || !$jury->isActive()
            || !$this->isGranted('ROLE_JURY')
        ) {
            throw $this->createAccessDeniedException(
                'Seul un jury actif peut effectuer une évaluation.'
            );
        }

        if (!$e->getJurys()->contains($jury)) {
            throw $this->createAccessDeniedException(
                'Vous n’êtes pas affecté à cet entretien.'
            );
        }

        $ev = $em->getRepository(Evaluation::class)->findOneBy([
            'entretien' => $e,
            'jury' => $jury,
        ]);

        if (!$ev instanceof Evaluation) {
            $ev = new Evaluation();
            $ev->setEntretien($e);
            $ev->setJury($jury);
        }

        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'evaluation_' . $e->getId(),
                (string) $r->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Le formulaire a expiré. Veuillez le recharger.'
                );
            }

            $donnees = $r->request->all('evaluation');

            try {
                $scores = array_intersect_key(
                    $donnees,
                    array_flip(array_keys(Evaluation::bareme()))
                );

                foreach (Evaluation::bareme() as $critere => $maximum) {
                    if (
                        !array_key_exists($critere, $scores)
                        || $scores[$critere] === ''
                    ) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Veuillez renseigner la note du critère "%s".',
                                $critere
                            )
                        );
                    }
                }

                $ev->setScores($scores);

                $avis = (string) ($donnees['decision'] ?? 'EN_ATTENTE');
                $ev->setDecision($avis);

                $observations = trim(
                    (string) ($donnees['observations'] ?? '')
                );

                $ev->setObservations(
                    $observations !== '' ? $observations : null
                );

                $ev->setEntretien($e);
                $ev->setJury($jury);

                $em->persist($ev);
                $em->flush();

                $this->addFlash('success', 'Évaluation enregistrée avec succès !');

                return $this->redirectToRoute('recruitment_interviews');
            } catch (\InvalidArgumentException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('recrutement/evaluation.html.twig', [
            'entretien' => $e,
            'evaluation' => $ev,
            'bareme' => Evaluation::bareme(),
            'juryConnecte' => $jury,
        ]);
    }
}