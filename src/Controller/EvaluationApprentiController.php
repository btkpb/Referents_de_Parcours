<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EvaluationApprentiController extends AbstractController
{
    private const TYPES = ['DEBUT', 'PERIODIQUE', 'STAGE', 'FINALE'];

    private const SCORES = [
        'score_technique',
        'score_qualite',
        'score_securite',
        'score_assiduite',
        'score_autonomie',
    ];

    private function verifierLecture(): void
    {
        if (
            !$this->isGranted('ROLE_GESTIONNAIRE')
            && !$this->isGranted('ROLE_FORMATEUR')
            && !$this->isGranted('ROLE_REFERENT')
        ) {
            throw $this->createAccessDeniedException();
        }
    }

    private function verifierCreation(): void
    {
        if (
            !$this->isGranted('ROLE_GESTIONNAIRE')
            && !$this->isGranted('ROLE_FORMATEUR')
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas creer ou modifier une evaluation pedagogique.'
            );
        }
    }

    private function utilisateurId(): int
    {
        $user = $this->getUser();

        if (!$user instanceof User || $user->getId() === null) {
            throw $this->createAccessDeniedException();
        }

        return $user->getId();
    }

    private function peutModifier(array $item): bool
    {
        if ($this->isGranted('ROLE_GESTIONNAIRE')) {
            return true;
        }

        return $this->isGranted('ROLE_FORMATEUR')
            && (int) ($item['evaluateur_id'] ?? 0) === $this->utilisateurId();
    }

    #[Route('/evaluations-apprentis', name: 'evaluation_apprenti_index', methods: ['GET'])]
    public function index(Connection $db): Response
    {
        $this->verifierLecture();

        $sql = '
            SELECT e.*, c.nom AS candidat_nom, c.prenoms AS candidat_prenoms,
                   c.numero AS candidat_numero,
                   u.nom AS auteur_nom, u.prenoms AS auteur_prenoms
            FROM evaluation_apprenti e
            INNER JOIN candidat c ON c.id = e.candidat_id
            LEFT JOIN app_user u ON u.id = e.evaluateur_id
        ';

        $params = [];

        if ($this->isGranted('ROLE_GESTIONNAIRE')) {
            $sql .= ' ORDER BY e.date_evaluation DESC, e.id DESC';
        } elseif ($this->isGranted('ROLE_FORMATEUR')) {
            $sql .= ' WHERE e.evaluateur_id = :uid
                      ORDER BY e.date_evaluation DESC, e.id DESC';
            $params['uid'] = $this->utilisateurId();
        } else {
            $sql .= " WHERE e.statut = 'PUBLIEE'
                      ORDER BY e.date_evaluation DESC, e.id DESC";
        }

        $items = $db->fetchAllAssociative($sql, $params);

        foreach ($items as &$item) {
            $item['total'] = round(
                (float) $item['score_technique']
                + (float) $item['score_qualite']
                + (float) $item['score_securite']
                + (float) $item['score_assiduite']
                + (float) $item['score_autonomie'],
                2
            );

            $item['peutModifier'] =
                $this->isGranted('ROLE_FORMATEUR')
                && !$this->isGranted('ROLE_GESTIONNAIRE')
                && $item['evaluateur_id'] !== null
                && (int) $item['evaluateur_id'] === (int) $this->utilisateurId();

            $item['peutPublier'] = $item['peutModifier']
                && $item['statut'] === 'BROUILLON';
        }
        unset($item);

        return $this->render('evaluation_apprenti/index.html.twig', [
            'evaluations' => $items,
            'peutCreer' => $this->isGranted('ROLE_FORMATEUR')
                && !$this->isGranted('ROLE_GESTIONNAIRE'),
        ]);
    }

    #[Route('/evaluations-apprentis/nouvelle', name: 'evaluation_apprenti_new', methods: ['GET', 'POST'])]
    public function nouvelle(Request $request, Connection $db): Response
    {
        // BEGIN E2C_STRICT_NOUVELLE
        if (
            !$this->isGranted('ROLE_FORMATEUR')
            || $this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            throw $this->createAccessDeniedException(
                'Cette action est réservée aux formateurs, et non aux gestionnaires.'
            );
        }
        // END E2C_STRICT_NOUVELLE
        $this->verifierCreation();

        return $this->formulaire(null, $request, $db);
    }

    #[Route('/evaluations-apprentis/{id}/modifier', name: 'evaluation_apprenti_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(int $id, Request $request, Connection $db): Response
    {
        // BEGIN E2C_STRICT_MODIFIER
        if (
            !$this->isGranted('ROLE_FORMATEUR')
            || $this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            throw $this->createAccessDeniedException(
                'Cette action est réservée aux formateurs, et non aux gestionnaires.'
            );
        }
        // END E2C_STRICT_MODIFIER
        $this->verifierCreation();

        $item = $db->fetchAssociative(
            'SELECT * FROM evaluation_apprenti WHERE id = :id',
            ['id' => $id]
        );

        if (!$item) {
            throw $this->createNotFoundException('Evaluation introuvable.');
        }

        if (
            $item["evaluateur_id"] === null
            || (int) $item["evaluateur_id"] !== (int) $this->utilisateurId()
        ) {
            throw $this->createAccessDeniedException(
                "Vous ne pouvez modifier ou publier que vos propres évaluations."
            );
        }
        
        if (!$this->peutModifier($item)) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez modifier que vos propres evaluations.'
            );
        }

        return $this->formulaire($item, $request, $db);
    }

    private function formulaire(?array $item, Request $request, Connection $db): Response
    {
        $edition = $item !== null;
        $id = $edition ? (int) $item['id'] : null;
        $tokenId = 'evaluation_apprenti_form_' . ($id ?? 'nouvelle');

        $item ??= [
            'candidat_id' => '',
            'date_evaluation' => date('Y-m-d'),
            'type_evaluation' => 'PERIODIQUE',
            'score_technique' => '0',
            'score_qualite' => '0',
            'score_securite' => '0',
            'score_assiduite' => '0',
            'score_autonomie' => '0',
            'commentaire' => '',
            'points_forts' => '',
            'axes_amelioration' => '',
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                $tokenId,
                (string) $request->request->get('_token', '')
            )) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $item['date_evaluation'] = trim((string) $request->request->get('date_evaluation', ''));
            $item['type_evaluation'] = trim((string) $request->request->get('type_evaluation', ''));
            $item['commentaire'] = trim((string) $request->request->get('commentaire', ''));
            $item['points_forts'] = trim((string) $request->request->get('points_forts', ''));
            $item['axes_amelioration'] = trim((string) $request->request->get('axes_amelioration', ''));

            if (!$edition) {
                $item['candidat_id'] = $request->request->getInt('candidat_id');
            }

            $erreurs = [];

            if (!in_array($item['type_evaluation'], self::TYPES, true)) {
                $erreurs[] = 'Choisissez un type d evaluation valide.';
            }

            $date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                (string) $item['date_evaluation']
            );

            if (
                !$date
                || $date->format('Y-m-d') !== $item['date_evaluation']
            ) {
                $erreurs[] = 'La date de l evaluation est invalide.';
            }

            foreach (['commentaire', 'points_forts', 'axes_amelioration'] as $champ) {
                if (mb_strlen((string) $item[$champ], 'UTF-8') > 10000) {
                    $erreurs[] = 'Les commentaires ne doivent pas depasser 10 000 caracteres.';
                    break;
                }
            }

            $scores = [];

            foreach (self::SCORES as $champ) {
                $valeur = $request->request->get($champ, $item[$champ] ?? '0');

                if (!is_numeric($valeur) || (float) $valeur < 0 || (float) $valeur > 20) {
                    $erreurs[] = 'Chaque note doit etre comprise entre 0 et 20.';
                    break;
                }

                $scores[$champ] = round((float) $valeur, 2);
                $item[$champ] = $scores[$champ];
            }

            if ($edition) {
                $candidatId = (int) $item['candidat_id'];
                $candidatExiste = (int) $db->fetchOne(
                    'SELECT COUNT(*) FROM candidat WHERE id = :id',
                    ['id' => $candidatId]
                ) > 0;
            } else {
                $candidatId = (int) $item['candidat_id'];
                $candidatExiste = (int) $db->fetchOne(
                    "SELECT COUNT(*)
                     FROM candidat c
                     INNER JOIN admission a ON a.candidat_id = c.id
                     WHERE c.id = :id AND a.decision = 'ADMIS'",
                    ['id' => $candidatId]
                ) > 0;
            }

            if (!$candidatExiste) {
                $erreurs[] = 'Selectionnez un apprenti admis valide.';
            }

            if ($erreurs === []) {
                $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

                $donnees = [
                    'candidat_id' => $candidatId,
                    'date_evaluation' => $date->format('Y-m-d'),
                    'type_evaluation' => $item['type_evaluation'],
                    'score_technique' => $scores['score_technique'],
                    'score_qualite' => $scores['score_qualite'],
                    'score_securite' => $scores['score_securite'],
                    'score_assiduite' => $scores['score_assiduite'],
                    'score_autonomie' => $scores['score_autonomie'],
                    'commentaire' => $item['commentaire'] !== '' ? $item['commentaire'] : null,
                    'points_forts' => $item['points_forts'] !== '' ? $item['points_forts'] : null,
                    'axes_amelioration' => $item['axes_amelioration'] !== '' ? $item['axes_amelioration'] : null,
                    'statut' => 'BROUILLON',
                    'updated_at' => $now,
                ];

                if ($edition) {
                    unset($donnees['candidat_id']);
                    $db->update('evaluation_apprenti', $donnees, ['id' => $id]);
                    $this->addFlash(
                        'success',
                        'Evaluation modifiee. Elle repasse en brouillon : republiez-la apres verification.'
                    );
                } else {
                    $donnees['evaluateur_id'] = $this->utilisateurId();
                    $donnees['created_at'] = $now;
                    $db->insert('evaluation_apprenti', $donnees);
                    $this->addFlash('success', 'Evaluation enregistree en brouillon.');
                }

                return $this->redirectToRoute('evaluation_apprenti_index');
            }

            foreach ($erreurs as $erreur) {
                $this->addFlash('danger', $erreur);
            }
        }

        if ($edition) {
            $candidats = $db->fetchAllAssociative(
                "SELECT DISTINCT c.id, c.nom, c.prenoms, c.numero
                 FROM candidat c
                 LEFT JOIN admission a ON a.candidat_id = c.id
                 WHERE a.decision = 'ADMIS' OR c.id = :id
                 ORDER BY c.nom, c.prenoms",
                ['id' => (int) $item['candidat_id']]
            );
        } else {
            $candidats = $db->fetchAllAssociative(
                "SELECT c.id, c.nom, c.prenoms, c.numero
                 FROM candidat c
                 INNER JOIN admission a ON a.candidat_id = c.id
                 WHERE a.decision = 'ADMIS'
                 ORDER BY c.nom, c.prenoms"
            );
        }

        return $this->render('evaluation_apprenti/form.html.twig', [
            'item' => $item,
            'edition' => $edition,
            'candidats' => $candidats,
            'tokenId' => $tokenId,
        ]);
    }

    #[Route('/evaluations-apprentis/{id}/publier', name: 'evaluation_apprenti_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publier(int $id, Request $request, Connection $db): Response
    {
        // BEGIN E2C_STRICT_PUBLIER
        if (
            !$this->isGranted('ROLE_FORMATEUR')
            || $this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            throw $this->createAccessDeniedException(
                'Cette action est réservée aux formateurs, et non aux gestionnaires.'
            );
        }
        // END E2C_STRICT_PUBLIER
        $this->verifierCreation();

        $item = $db->fetchAssociative(
            'SELECT id, evaluateur_id, statut FROM evaluation_apprenti WHERE id = :id',
            ['id' => $id]
        );

        if (!$item) {
            throw $this->createNotFoundException('Evaluation introuvable.');
        }

        if (
            $item["evaluateur_id"] === null
            || (int) $item["evaluateur_id"] !== (int) $this->utilisateurId()
        ) {
            throw $this->createAccessDeniedException(
                "Vous ne pouvez modifier ou publier que vos propres évaluations."
            );
        }
        
        if (!$this->peutModifier($item)) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'publier_evaluation_' . $id,
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $db->update(
            'evaluation_apprenti',
            [
                'statut' => 'PUBLIEE',
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            ['id' => $id]
        );

        $this->addFlash('success', 'Evaluation publiee dans l espace de l apprenti.');

        return $this->redirectToRoute('evaluation_apprenti_index');
    }

    #[Route('/espace-apprenti/evaluations-pedagogiques', name: 'apprenti_evaluations_pedagogiques', methods: ['GET'])]
    public function mesEvaluationsPedagogiques(Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_APPRENTI');

        $user = $this->getUser();

        if (!$user instanceof User || !$user->getCandidat()) {
            return $this->render('evaluation_apprenti/mes_evaluations.html.twig', [
                'evaluations' => [],
                'candidatAssocie' => false,
            ]);
        }

        $candidat = $user->getCandidat();

        $evaluations = $db->fetchAllAssociative(
            "SELECT e.*, u.nom AS auteur_nom, u.prenoms AS auteur_prenoms
             FROM evaluation_apprenti e
             LEFT JOIN app_user u ON u.id = e.evaluateur_id
             WHERE e.candidat_id = :candidat AND e.statut = 'PUBLIEE'
             ORDER BY e.date_evaluation DESC, e.id DESC",
            ['candidat' => $candidat->getId()]
        );

        foreach ($evaluations as &$evaluation) {
            $evaluation['total'] = round(
                (float) $evaluation['score_technique']
                + (float) $evaluation['score_qualite']
                + (float) $evaluation['score_securite']
                + (float) $evaluation['score_assiduite']
                + (float) $evaluation['score_autonomie'],
                2
            );
        }
        unset($evaluation);

        return $this->render('evaluation_apprenti/mes_evaluations.html.twig', [
            'evaluations' => $evaluations,
            'candidatAssocie' => true,
        ]);
    }
}