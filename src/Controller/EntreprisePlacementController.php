<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Admission;
use App\Entity\AffectationEntrepriseApprenti;
use App\Entity\Chantier;
use App\Entity\Candidat;
use App\Entity\EntrepriseStage;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/placements', name: 'admin_placements_')]
class EntreprisePlacementController extends AbstractController
{
    private function verifierAcces(): void
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        $statut = (string) $request->query->get('statut', '');

        if (!in_array($statut, ['', 'EN_COURS', 'TERMINEE', 'ANNULEE'], true)) {
            $statut = '';
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limite = 15;

        $qb = $em->getRepository(AffectationEntrepriseApprenti::class)
            ->createQueryBuilder('p')
            ->addSelect('c', 'e', 'ch')
            ->innerJoin('p.candidat', 'c')
            ->innerJoin('p.entreprise', 'e')
            ->leftJoin('p.chantier', 'ch');

        if ($statut !== '') {
            $qb->andWhere('p.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $nombrePages = max(1, (int) ceil($total / $limite));
        $page = min($page, $nombrePages);

        $placements = $qb
            ->orderBy('p.dateDebut', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        $repository = $em->getRepository(AffectationEntrepriseApprenti::class);

        $statistiques = [
            'total' => (int) $repository->count([]),
            'enCours' => (int) $repository->count([
                'statut' => AffectationEntrepriseApprenti::STATUT_EN_COURS,
                'dateFin' => null,
            ]),
            'terminees' => (int) $repository->count([
                'statut' => AffectationEntrepriseApprenti::STATUT_TERMINEE,
            ]),
        ];

        return $this->render('admin/placements/index.html.twig', [
            'placements' => $placements,
            'statutFiltre' => $statut,
            'statistiques' => $statistiques,
            'page' => $page,
            'nombrePages' => $nombrePages,
            'total' => $total,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    public function nouveau(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        $admissions = $em->getRepository(Candidat::class)
            ->createQueryBuilder('c')
            ->where(
                'EXISTS (
                    SELECT a.id
                    FROM App\Entity\Admission a
                    WHERE a.candidat = c
                    AND a.decision = :decision
                )'
            )
            ->andWhere(
                'NOT EXISTS (
                    SELECT p2.id
                    FROM App\Entity\AffectationEntrepriseApprenti p2
                    WHERE p2.candidat = c
                    AND p2.statut = :enCours
                    AND p2.dateFin IS NULL
                )'
            )
            ->setParameter('decision', 'ADMIS')
            ->setParameter('enCours', AffectationEntrepriseApprenti::STATUT_EN_COURS)
            ->orderBy('c.nom', 'ASC')
            ->addOrderBy('c.prenoms', 'ASC')
            ->getQuery()
            ->getResult();
        $entreprises = $em->getRepository(EntrepriseStage::class)
            ->findBy(
                ['statut' => EntrepriseStage::STATUT_ACTIVE],
                ['raisonSociale' => 'ASC']
            );

        $chantiers = $em->getRepository(Chantier::class)
            ->findBy(
                ['statut' => 'ACTIF'],
                ['nom' => 'ASC']
            );

        $erreurs = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'placement_nouveau',
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Jeton de sÃ©curitÃ© invalide.');
            }

            $candidatId = $request->request->getInt('candidat');
            $entrepriseId = $request->request->getInt('entreprise');
            $chantierId = $request->request->getInt('chantier');
            $dateDebutBrute = trim((string) $request->request->get('dateDebut', ''));
            $observations = trim((string) $request->request->get('observations', ''));

            $candidat = $candidatId > 0
                ? $em->getRepository(Candidat::class)->find($candidatId)
                : null;

            $entreprise = $entrepriseId > 0
                ? $em->getRepository(EntrepriseStage::class)->find($entrepriseId)
                : null;

            $chantier = $chantierId > 0
                ? $em->getRepository(Chantier::class)->find($chantierId)
                : null;

            $dateDebut = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebutBrute);
            $dateErrors = \DateTimeImmutable::getLastErrors();

            if (!$candidat instanceof Candidat) {
                $erreurs[] = 'Veuillez sÃ©lectionner un apprenti valide.';
            } else {
                $admission = $em->getRepository(Admission::class)->findOneBy([
                    'candidat' => $candidat,
                    'decision' => 'ADMIS',
                ]);

                if (!$admission instanceof Admission) {
                    $erreurs[] = 'Cet apprenti ne possÃ¨de pas de dÃ©cision dâ€™admission ADMIS.';
                }

                $placementActif = $em->getRepository(AffectationEntrepriseApprenti::class)
                    ->findOneBy([
                        'candidat' => $candidat,
                        'statut' => AffectationEntrepriseApprenti::STATUT_EN_COURS,
                        'dateFin' => null,
                    ]);

                if ($placementActif instanceof AffectationEntrepriseApprenti) {
                    $erreurs[] = 'Cet apprenti possÃ¨de dÃ©jÃ  un placement en cours.';
                }
            }

            if (!$entreprise instanceof EntrepriseStage
                || $entreprise->getStatut() !== EntrepriseStage::STATUT_ACTIVE
            ) {
                $erreurs[] = 'Veuillez sÃ©lectionner une entreprise active.';
            }

            if ($chantierId > 0 && (
                !$chantier instanceof Chantier
                || $chantier->getStatut() !== 'ACTIF'
            )) {
                $erreurs[] = 'Le chantier sÃ©lectionnÃ© est introuvable ou inactif.';
            }

            if (
                !$dateDebut
                || ($dateErrors !== false && (
                    $dateErrors['warning_count'] > 0
                    || $dateErrors['error_count'] > 0
                ))
                || $dateDebut->format('Y-m-d') !== $dateDebutBrute
            ) {
                $erreurs[] = 'Veuillez saisir une date de dÃ©but valide.';
            }

            if (mb_strlen($observations) > 10000) {
                $erreurs[] = 'Les observations ne doivent pas dÃ©passer 10 000 caractÃ¨res.';
            }

            if ($erreurs === []) {
                $utilisateur = $this->getUser();

                $placement = $em->wrapInTransaction(
                    function (EntityManagerInterface $em) use (
                        $candidatId,
                        $entrepriseId,
                        $chantierId,
                        $dateDebut,
                        $observations,
                        $utilisateur
                    ): ?AffectationEntrepriseApprenti {
                        $candidatVerrouille = $em->find(Candidat::class, $candidatId);

                        if (!$candidatVerrouille instanceof Candidat) {
                            return null;
                        }

                        // Verrouillage de l'apprenti pendant toute la transaction.
                        $em->lock($candidatVerrouille, LockMode::PESSIMISTIC_WRITE);

                        // Nouvelle verification de la decision d'admission.
                        $admissionVerifiee = $em->getRepository(Admission::class)->findOneBy([
                            'candidat' => $candidatVerrouille,
                            'decision' => 'ADMIS',
                        ]);

                        if (!$admissionVerifiee instanceof Admission) {
                            return null;
                        }

                        // Nouvelle verification de l'absence de placement actif.
                        $placementActifVerifie = $em
                            ->getRepository(AffectationEntrepriseApprenti::class)
                            ->findOneBy([
                                'candidat' => $candidatVerrouille,
                                'statut' => AffectationEntrepriseApprenti::STATUT_EN_COURS,
                                'dateFin' => null,
                            ]);

                        if ($placementActifVerifie instanceof AffectationEntrepriseApprenti) {
                            return null;
                        }

                        // Recharger et verifier l'entreprise au moment de l'enregistrement.
                        $entrepriseVerifiee = $em->find(EntrepriseStage::class, $entrepriseId);

                        if (
                            !$entrepriseVerifiee instanceof EntrepriseStage
                            || $entrepriseVerifiee->getStatut() !== EntrepriseStage::STATUT_ACTIVE
                        ) {
                            return null;
                        }

                        // Le chantier reste facultatif.
                        $chantierVerifie = null;

                        if ($chantierId > 0) {
                            $chantierVerifie = $em->find(Chantier::class, $chantierId);

                            if (
                                !$chantierVerifie instanceof Chantier
                                || $chantierVerifie->getStatut() !== 'ACTIF'
                            ) {
                                return null;
                            }
                        }

                        $placement = (new AffectationEntrepriseApprenti())
                            ->setCandidat($candidatVerrouille)
                            ->setEntreprise($entrepriseVerifiee)
                            ->setChantier($chantierVerifie)
                            ->setDateDebut($dateDebut)
                            ->setDateFin(null)
                            ->setStatut(AffectationEntrepriseApprenti::STATUT_EN_COURS)
                            ->setObservations($observations !== '' ? $observations : null);

                        if ($utilisateur instanceof User) {
                            $placement->setCreePar($utilisateur);
                        }

                        $em->persist($placement);

                        return $placement;
                    }
                );

                if (!$placement instanceof AffectationEntrepriseApprenti) {
                    $erreurs[] = "L'apprenti, son admission, son placement ou l'entreprise a change. Actualisez le formulaire et verifiez les informations.";
                } else {
                    $this->addFlash('success', 'Le placement en entreprise a ete enregistre.');

                    return $this->redirectToRoute('admin_placements_show', [
                        'id' => $placement->getId(),
                    ]);
                }
            }
        }
        return $this->render('admin/placements/form.html.twig', [
            'candidats' => $admissions,
            'entreprises' => $entreprises,
            'chantiers' => $chantiers,
            'erreurs' => $erreurs,
            'dateDebutDefaut' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function afficher(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        $placement = $em->getRepository(AffectationEntrepriseApprenti::class)
            ->createQueryBuilder('p')
            ->addSelect('c', 'e', 'ch', 'u')
            ->innerJoin('p.candidat', 'c')
            ->innerJoin('p.entreprise', 'e')
            ->leftJoin('p.chantier', 'ch')
            ->leftJoin('p.creePar', 'u')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$placement instanceof AffectationEntrepriseApprenti) {
            throw $this->createNotFoundException('Placement introuvable.');
        }

        return $this->render('admin/placements/show.html.twig', [
            'placement' => $placement,
        ]);
    }

    #[Route('/{id}/cloturer', name: 'close', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cloturer(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        $placement = $em->getRepository(AffectationEntrepriseApprenti::class)->find($id);

        if (!$placement instanceof AffectationEntrepriseApprenti) {
            throw $this->createNotFoundException('Placement introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'placement_cloturer_' . $id,
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sÃ©curitÃ© invalide.');
        }

        if (!$placement->estActive()) {
            $this->addFlash('warning', 'Ce placement est dÃ©jÃ  clÃ´turÃ© ou annulÃ©.');

            return $this->redirectToRoute('admin_placements_show', ['id' => $id]);
        }

        $placement->setDateFin(new \DateTimeImmutable('today'));
        $placement->setStatut(AffectationEntrepriseApprenti::STATUT_TERMINEE);

        $em->flush();

        $this->addFlash('success', 'Le placement a Ã©tÃ© clÃ´turÃ©. Son historique est conservÃ©.');

        return $this->redirectToRoute('admin_placements_show', ['id' => $id]);
    }
}
