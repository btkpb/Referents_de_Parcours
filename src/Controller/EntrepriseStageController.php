<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EntrepriseStage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/entreprises', name: 'admin_entreprises_')]
class EntrepriseStageController extends AbstractController
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    private function verifierAcces(): void
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');
    }

    private function dossierFichiers(): string
    {
        $dossier = $this->getParameter('kernel.project_dir')
            . '/var/uploads/entreprises';

        if (!is_dir($dossier) && !mkdir($dossier, 0750, true) && !is_dir($dossier)) {
            throw new \RuntimeException("Impossible de créer le dossier privé des entreprises.");
        }

        return $dossier;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierAcces();

        $recherche = trim((string) $request->query->get('q', ''));
        $statut = (string) $request->query->get('statut', '');

        if (!in_array($statut, ['', 'ACTIVE', 'INACTIVE'], true)) {
            $statut = '';
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limite = 15;

        $qb = $em->getRepository(EntrepriseStage::class)->createQueryBuilder('e');

        if ($recherche !== '') {
            $qb->andWhere(
                'LOWER(e.raisonSociale) LIKE :q
                 OR LOWER(e.sigle) LIKE :q
                 OR LOWER(e.ville) LIKE :q
                 OR LOWER(e.secteurActivite) LIKE :q'
            )->setParameter('q', '%' . mb_strtolower($recherche) . '%');
        }

        if ($statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $total = (clone $qb)
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $nombrePages = max(1, (int) ceil((int) $total / $limite));
        $page = min($page, $nombrePages);

        $entreprises = $qb
            ->orderBy('e.raisonSociale', 'ASC')
            ->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        $statistiques = [
            'total' => (int) $em->getRepository(EntrepriseStage::class)->count([]),
            'actives' => (int) $em->getRepository(EntrepriseStage::class)->count([
                'statut' => EntrepriseStage::STATUT_ACTIVE,
            ]),
            'inactives' => (int) $em->getRepository(EntrepriseStage::class)->count([
                'statut' => EntrepriseStage::STATUT_INACTIVE,
            ]),
        ];

        return $this->render('admin/entreprises/index.html.twig', [
            'entreprises' => $entreprises,
            'recherche' => $recherche,
            'statutFiltre' => $statut,
            'statistiques' => $statistiques,
            'page' => $page,
            'nombrePages' => $nombrePages,
            'total' => (int) $total,
        ]);
    }

    #[Route('/nouvelle', name: 'new', methods: ['GET', 'POST'])]
    public function nouvelle(Request $request, EntityManagerInterface $em): Response
    {
        $this->verifierAcces();

        return $this->formulaire(new EntrepriseStage(), $request, $em, false);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        $entreprise = $em->getRepository(EntrepriseStage::class)->find($id);

        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        return $this->formulaire($entreprise, $request, $em, true);
    }

    private function formulaire(
        EntrepriseStage $entreprise,
        Request $request,
        EntityManagerInterface $em,
        bool $edition
    ): Response {
        $anciensFichiers = [
            'logo' => $entreprise->getLogoFichier(),
            'cachet' => $entreprise->getCachetFichier(),
        ];

        $champs = [
            'raisonSociale',
            'sigle',
            'secteurActivite',
            'adresse',
            'ville',
            'telephone',
            'telephoneSecondaire',
            'email',
            'nomResponsable',
            'fonctionResponsable',
            'telephoneResponsable',
            'nomTuteur',
            'fonctionTuteur',
            'telephoneTuteur',
            'emailTuteur',
        ];

        $erreur = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'entreprise_form',
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
            }

            foreach ($champs as $champ) {
                $valeur = trim((string) $request->request->get($champ, ''));
                $setter = 'set' . ucfirst($champ);

                if ($champ === 'raisonSociale') {
                    $entreprise->$setter($valeur);
                } else {
                    $entreprise->$setter($valeur !== '' ? $valeur : null);
                }
            }

            if ($entreprise->getRaisonSociale() === '') {
                $erreur = 'La raison sociale est obligatoire.';
            } elseif (
                $entreprise->getEmail() !== null
                && !filter_var($entreprise->getEmail(), FILTER_VALIDATE_EMAIL)
            ) {
                $erreur = "L'adresse électronique de l'entreprise est invalide.";
            } elseif (
                $entreprise->getEmailTuteur() !== null
                && !filter_var($entreprise->getEmailTuteur(), FILTER_VALIDATE_EMAIL)
            ) {
                $erreur = "L'adresse électronique du tuteur est invalide.";
            }

            $uploads = [];
            $nouveauxFichiers = [];

            foreach (['logo', 'cachet'] as $type) {
                $fichier = $request->files->get($type);

                if ($fichier === null) {
                    continue;
                }

                if (!$fichier instanceof UploadedFile || !$fichier->isValid()) {
                    $erreur = 'Un fichier transmis est invalide.';
                    break;
                }

                if ($fichier->getSize() > self::MAX_FILE_SIZE) {
                    $erreur = 'Chaque image doit faire au maximum 5 Mo.';
                    break;
                }

                $mime = $fichier->getMimeType();
                $extension = match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => null,
                };

                if ($extension === null) {
                    $erreur = 'Formats autorisés pour le logo et le cachet : JPG, PNG et WEBP.';
                    break;
                }

                $uploads[$type] = [
                    'fichier' => $fichier,
                    'extension' => $extension,
                ];
            }

            if ($erreur === null) {
                try {
                    $dossier = $this->dossierFichiers();

                    foreach ($uploads as $type => $upload) {
                        $nom = bin2hex(random_bytes(24)) . '.' . $upload['extension'];

                        $upload['fichier']->move($dossier, $nom);
                        $nouveauxFichiers[] = $nom;

                        if ($type === 'logo') {
                            $entreprise->setLogoFichier($nom);
                        } else {
                            $entreprise->setCachetFichier($nom);
                        }
                    }

                    if (!$edition) {
                        $entreprise->setStatut(EntrepriseStage::STATUT_ACTIVE);
                        $em->persist($entreprise);
                    }

                    $em->flush();

                    foreach (['logo', 'cachet'] as $type) {
                        $ancien = $anciensFichiers[$type];
                        $actuel = $type === 'logo'
                            ? $entreprise->getLogoFichier()
                            : $entreprise->getCachetFichier();

                        if ($ancien && $ancien !== $actuel) {
                            $ancienChemin = $dossier . DIRECTORY_SEPARATOR . basename($ancien);

                            if (is_file($ancienChemin)) {
                                @unlink($ancienChemin);
                            }
                        }
                    }

                    $this->addFlash('success', $edition
                        ? 'Les informations de l’entreprise ont été mises à jour.'
                        : 'L’entreprise a été enregistrée.');

                    return $this->redirectToRoute('admin_entreprises_show', [
                        'id' => $entreprise->getId(),
                    ]);
                } catch (\Throwable $e) {
                    foreach ($nouveauxFichiers as $nom) {
                        $chemin = $this->dossierFichiers() . DIRECTORY_SEPARATOR . basename($nom);

                        if (is_file($chemin)) {
                            @unlink($chemin);
                        }
                    }

                    $erreur = "L'enregistrement a échoué. Vérifiez les données et les fichiers transmis.";
                }
            }
        }

        return $this->render('admin/entreprises/form.html.twig', [
            'entreprise' => $entreprise,
            'edition' => $edition,
            'erreur' => $erreur,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function afficher(int $id, EntityManagerInterface $em): Response
    {
        $this->verifierAcces();

        $entreprise = $em->getRepository(EntrepriseStage::class)->find($id);

        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        return $this->render('admin/entreprises/show.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/{id}/statut', name: 'toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function changerStatut(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        $entreprise = $em->getRepository(EntrepriseStage::class)->find($id);

        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'entreprise_statut_' . $id,
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $nouveauStatut = $entreprise->getStatut() === EntrepriseStage::STATUT_ACTIVE
            ? EntrepriseStage::STATUT_INACTIVE
            : EntrepriseStage::STATUT_ACTIVE;

        $entreprise->setStatut($nouveauStatut);
        $em->flush();

        $this->addFlash('success', $nouveauStatut === EntrepriseStage::STATUT_ACTIVE
            ? 'Entreprise activée.'
            : 'Entreprise désactivée.');

        return $this->redirectToRoute('admin_entreprises_show', ['id' => $id]);
    }

    #[Route('/{id}/fichier/{type}', name: 'fichier', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function fichier(
        int $id,
        string $type,
        EntityManagerInterface $em
    ): Response {
        $this->verifierAcces();

        if (!in_array($type, ['logo', 'cachet'], true)) {
            throw $this->createNotFoundException();
        }

        $entreprise = $em->getRepository(EntrepriseStage::class)->find($id);

        if (!$entreprise) {
            throw $this->createNotFoundException('Entreprise introuvable.');
        }

        $nom = $type === 'logo'
            ? $entreprise->getLogoFichier()
            : $entreprise->getCachetFichier();

        if (!$nom || basename($nom) !== $nom) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $chemin = $this->dossierFichiers() . DIRECTORY_SEPARATOR . $nom;

        if (!is_file($chemin)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $mime = match (strtolower(pathinfo($nom, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        $response = new BinaryFileResponse($chemin);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $type . '-' . $entreprise->getId() . '.' . pathinfo($nom, PATHINFO_EXTENSION)
        );

        return $response;
    }
}
