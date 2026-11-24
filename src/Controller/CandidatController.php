<?php

namespace App\Controller;

use App\Entity\Admission;
use App\Entity\Candidat;
use App\Entity\Campagne;
use App\Entity\User;
use App\Entity\DocumentApprenti;
use App\Entity\AffectationResponsableApprenti;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

class CandidatController extends AbstractController
{
    private const CATEGORIES = [
        'piece_identite',
        'diplome',
        'acte_naissance',
        'certificat_medical',
        'attestation_formation',
        'convention_stage',
        'evaluation',
        'autre',
    ];

    #[Route('/candidats', name: 'candidat_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $recherche = trim((string) $request->query->get('q', ''));
        $statut = trim((string) $request->query->get('statut', ''));
        $metier = trim((string) $request->query->get('metier', ''));

        $page = max(1, $request->query->getInt('page', 1));
        $limite = 10;

        $repository = $em->getRepository(Candidat::class);

        $qb = $repository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        if ($recherche !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(c.numero) LIKE :recherche',
                    'LOWER(c.nom) LIKE :recherche',
                    'LOWER(c.prenoms) LIKE :recherche',
                    'LOWER(c.telephone) LIKE :recherche',
                    'LOWER(c.metier) LIKE :recherche'
                )
            )->setParameter(
                'recherche',
                '%' . mb_strtolower($recherche) . '%'
            );
        }

        if ($statut !== '') {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($metier !== '') {
            $qb->andWhere('c.metier = :metier')
                ->setParameter('metier', $metier);
        }

        $qb->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);

        $total = count($paginator);
        $nombrePages = max(1, (int) ceil($total / $limite));

        if ($page > $nombrePages) {
            $page = $nombrePages;
            $qb->setFirstResult(($page - 1) * $limite);
            $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
        }

        $statistiques = [
            'total' => $repository->count([]),
            'aVerifier' => $repository->count(['statut' => 'A_VERIFIER']),
            'eligible' => $repository->count(['statut' => 'ELIGIBLE']),
        ];

        $metiers = $em->createQuery(
            'SELECT DISTINCT c.metier
             FROM App\Entity\Candidat c
             WHERE c.metier IS NOT NULL AND c.metier <> \'\'
             ORDER BY c.metier ASC'
        )->getSingleColumnResult();

        return $this->render('candidat/index.html.twig', [
            'candidats' => $paginator,
            'recherche' => $recherche,
            'statutFiltre' => $statut,
            'metierFiltre' => $metier,
            'metiers' => $metiers,
            'statistiques' => $statistiques,
            'page' => $page,
            'nombrePages' => $nombrePages,
            'total' => $total,
        ]);
    }

    #[Route('/candidats/{id}', name: 'candidat_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Candidat $c, EntityManagerInterface $em): Response
    {
        $utilisateur = $this->getUser();
        $accesAutorise = $this->isGranted('ROLE_GESTIONNAIRE');

        if (!$accesAutorise && $utilisateur instanceof User) {
            if (
                $this->isGranted('ROLE_REFERENT')
                || $this->isGranted('ROLE_REFERENT_PARCOURS')
            ) {
                $affectationReferent = $em
                    ->getRepository(AffectationResponsableApprenti::class)
                    ->findOneBy([
                        'candidat' => $c,
                        'responsable' => $utilisateur,
                        'typeResponsabilite' => AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS,
                        'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                        'dateFin' => null,
                    ]);

                $accesAutorise = $affectationReferent instanceof AffectationResponsableApprenti;
            }

            if (!$accesAutorise && $this->isGranted('ROLE_FORMATEUR')) {
                $affectationFormateur = $em
                    ->getRepository(AffectationResponsableApprenti::class)
                    ->findOneBy([
                        'candidat' => $c,
                        'responsable' => $utilisateur,
                        'typeResponsabilite' => AffectationResponsableApprenti::TYPE_FORMATEUR,
                        'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                        'dateFin' => null,
                    ]);

                $accesAutorise = $affectationFormateur instanceof AffectationResponsableApprenti;
            }
        }

        if (!$accesAutorise) {
            throw $this->createAccessDeniedException(
                'Vous ne disposez pas d une affectation active pour consulter ce dossier.'
            );
        }
        $isGestionnaire = $this->isGranted('ROLE_GESTIONNAIRE');

        $admission = $em->getRepository(Admission::class)
            ->findOneBy(['candidat' => $c]);

        $isAdmis = $admission instanceof Admission
            && $admission->getDecision() === 'ADMIS';

        $canManagePhoto = $isGestionnaire && $isAdmis;
        $canManageResponsables = $isGestionnaire && $isAdmis;

        $documents = $em->getRepository(DocumentApprenti::class)->findBy(
            ['candidat' => $c],
            ['dateAjout' => 'DESC']
        );

        // Les certificats médicaux restent réservés aux gestionnaires.
        if (!$isGestionnaire) {
            $documents = array_values(array_filter(
                $documents,
                static fn (DocumentApprenti $document): bool =>
                    $document->getCategorie() !== 'certificat_medical'
            ));
        }

        // Historique complet des trois responsabilités.
        $affectationsResponsables = $em
            ->getRepository(AffectationResponsableApprenti::class)
            ->findBy(
                ['candidat' => $c],
                ['dateDebut' => 'DESC', 'id' => 'DESC']
            );

        $responsablesActifs = [];

        foreach ($affectationsResponsables as $affectation) {
            if (
                $affectation instanceof AffectationResponsableApprenti
                && $affectation->estActive()
            ) {
                $type = $affectation->getTypeResponsabilite();

                // La plus récente est conservée en cas d'anomalie historique.
                if (!isset($responsablesActifs[$type])) {
                    $responsablesActifs[$type] = $affectation;
                }
            }
        }

        // Comptes actifs pouvant être affectés à chaque responsabilité.
        $responsablesDisponibles = [
            AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS => [],
            AffectationResponsableApprenti::TYPE_FORMATEUR => [],
            AffectationResponsableApprenti::TYPE_MAITRE_STAGE => [],
        ];

        if ($isAdmis && $canManageResponsables) {
            $utilisateursActifs = $em->getRepository(User::class)->findBy(
                ['active' => true],
                ['nom' => 'ASC', 'prenoms' => 'ASC']
            );

            foreach ($utilisateursActifs as $utilisateur) {
                if (!$utilisateur instanceof User) {
                    continue;
                }

                $roles = $utilisateur->getRoles();

                if (
                    in_array('ROLE_REFERENT_PARCOURS', $roles, true)
                    || in_array('ROLE_REFERENT', $roles, true)
                ) {
                    $responsablesDisponibles[
                        AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS
                    ][] = $utilisateur;
                }

                if (in_array('ROLE_FORMATEUR', $roles, true)) {
                    $responsablesDisponibles[
                        AffectationResponsableApprenti::TYPE_FORMATEUR
                    ][] = $utilisateur;
                }

                if (in_array('ROLE_MAITRE_STAGE', $roles, true)) {
                    $responsablesDisponibles[
                        AffectationResponsableApprenti::TYPE_MAITRE_STAGE
                    ][] = $utilisateur;
                }
            }
        }

        return $this->render('candidat/show.html.twig', [
            'candidat' => $c,
            'documents' => $documents,
            'canManageDocuments' => $isGestionnaire,
            'isAdmis' => $isAdmis,
            'canManagePhoto' => $canManagePhoto,
            'canManageResponsables' => $canManageResponsables,
            'affectationsResponsables' => $affectationsResponsables,
            'responsablesActifs' => $responsablesActifs,
            'responsablesDisponibles' => $responsablesDisponibles,
            'canManageEmail' => $isGestionnaire,
        ]);
    }

    #[Route('/candidats/{id}/email', name: 'candidat_email_update', methods: ['POST'])]
    public function modifierEmail(
        Candidat $c,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        if (!$this->isCsrfTokenValid(
            'modifier_email_' . $c->getId(),
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $email = trim((string) $request->request->get('email', ''));

        if ($email === '') {
            $email = null;
        } elseif (
            mb_strlen($email) > 180
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            $this->addFlash('danger', 'Adresse e-mail invalide. Vérifiez le format et la longueur.');

            return $this->redirectToRoute('candidat_show', [
                'id' => $c->getId(),
            ]);
        }

        $c->setEmail($email);
        $em->flush();

        $this->addFlash(
            'success',
            $email === null
                ? 'Adresse e-mail supprimée du dossier.'
                : 'Adresse e-mail du candidat mise à jour.'
        );

        return $this->redirectToRoute('candidat_show', [
            'id' => $c->getId(),
        ]);
    }
    #[Route(
        '/candidats/{id}/documents',
        name: 'candidat_document_add',
        methods: ['POST']
    )]
    public function ajouterDocument(
        Candidat $c,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $retour = $this->redirectToRoute('candidat_show', ['id' => $c->getId()]);

        if (!$this->isCsrfTokenValid(
            'ajouter_document_' . $c->getId(),
            (string) $request->request->get('_token', '')
        )) {
            $this->addFlash('danger', 'Jeton de securite invalide. Reessayez.');
            return $retour;
        }

        $categorie = (string) $request->request->get('categorie', '');
        $intitule = trim((string) $request->request->get('intitule', ''));

        if (!in_array($categorie, self::CATEGORIES, true)) {
            $this->addFlash('danger', 'Veuillez choisir une categorie valide.');
            return $retour;
        }

        if ($intitule === '' || mb_strlen($intitule, 'UTF-8') > 180) {
            $this->addFlash('danger', 'Le titre est obligatoire et ne doit pas depasser 180 caracteres.');
            return $retour;
        }

        $fichier = $request->files->get('document');

        if (!$fichier instanceof UploadedFile || !$fichier->isValid()) {
            $this->addFlash('danger', 'Selectionnez un fichier valide.');
            return $retour;
        }

        $taille = $fichier->getSize();

        if ($taille === null || $taille <= 0 || $taille > 10 * 1024 * 1024) {
            $this->addFlash('danger', 'Le fichier doit peser entre 1 octet et 10 Mo.');
            return $retour;
        }

        $mime = $fichier->getMimeType() ?? '';

        $extensionsAutorisees = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($extensionsAutorisees[$mime])) {
            $this->addFlash('danger', 'Formats autorises : PDF, JPG, JPEG et PNG.');
            return $retour;
        }

        $nomOriginal = basename(
            str_replace('\\', '/', $fichier->getClientOriginalName())
        );
        $nomOriginal = mb_substr($nomOriginal, 0, 255, 'UTF-8');

        if ($nomOriginal === '') {
            $nomOriginal = 'document.' . $extensionsAutorisees[$mime];
        }

        $nomStockage = bin2hex(random_bytes(16))
            . '.'
            . $extensionsAutorisees[$mime];

        $dossierStockage = $this->getParameter('kernel.project_dir')
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'dossiers';

        if (
            !is_dir($dossierStockage)
            && !mkdir($dossierStockage, 0770, true)
            && !is_dir($dossierStockage)
        ) {
            $this->addFlash('danger', 'Impossible de creer le dossier de stockage.');
            return $retour;
        }

        try {
            $fichier->move($dossierStockage, $nomStockage);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Le fichier n a pas pu etre enregistre.');
            return $retour;
        }

        $document = (new DocumentApprenti())
            ->setCandidat($c)
            ->setCategorie($categorie)
            ->setIntitule($intitule)
            ->setNomOriginal($nomOriginal)
            ->setNomStockage($nomStockage)
            ->setMimeType($mime)
            ->setTailleOctets((int) $taille)
            ->setDateAjout(new \DateTimeImmutable());

        $em->persist($document);
        $em->flush();

        $this->addFlash('success', 'Document ajoute au dossier administratif.');

        return $retour;
    }

    #[Route(
        '/candidats/{id}/documents/{documentId}/telecharger',
        name: 'candidat_document_download',
        methods: ['GET']
    )]
    public function telechargerDocument(
        Candidat $c,
        int $documentId,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $document = $em->getRepository(DocumentApprenti::class)->find($documentId);

        if (
            !($document instanceof DocumentApprenti)
            || $document->getCandidat()?->getId() !== $c->getId()
        ) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $chemin = $this->getParameter('kernel.project_dir')
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'dossiers'
            . DIRECTORY_SEPARATOR . basename($document->getNomStockage());

        if (!is_file($chemin)) {
            throw $this->createNotFoundException('Le fichier est introuvable sur le serveur.');
        }

        $response = new BinaryFileResponse($chemin);
        $response->headers->set('Content-Type', $document->getMimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getNomOriginal()
        );

        return $response;
    }

    #[Route(
        '/candidats/{id}/inscription/{type}/telecharger',
        name: 'candidat_inscription_document_download',
        requirements: ['type' => 'piece-identite|diplome'],
        methods: ['GET']
    )]
    public function telechargerDocumentInscription(
        Candidat $c,
        string $type
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $nom = match ($type) {
            'piece-identite' => $c->getPieceIdentiteFichier(),
            'diplome' => $c->getDiplomeFichier(),
            default => null,
        };

        if (!$nom) {
            throw $this->createNotFoundException(
                'Document non disponible.'
            );
        }

        $safeName = basename($nom);

        $chemin = $this->getParameter('kernel.project_dir')
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'inscriptions'
            . DIRECTORY_SEPARATOR . 'candidats'
            . DIRECTORY_SEPARATOR . $safeName;

        if (!is_file($chemin)) {
            throw $this->createNotFoundException(
                'Le fichier est introuvable sur le serveur.'
            );
        }

        $extension = strtolower(
            pathinfo($safeName, PATHINFO_EXTENSION)
        );

        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        $response = new BinaryFileResponse($chemin);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $nomTelechargement = (
            $type === 'piece-identite'
                ? 'piece-identite'
                : 'diplome'
        ) . '-' . $c->getId() . '.' . $extension;

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $nomTelechargement
        );

        return $response;
    }

    #[Route(
        '/candidats/{id}/photo-identite',
        name: 'candidat_photo_identite',
        methods: ['GET']
    )]
    public function afficherPhotoIdentite(
        Candidat $c,
        EntityManagerInterface $em
    ): Response {
        $estGestionnaire = $this->isGranted('ROLE_GESTIONNAIRE');
        $estReferent = $this->isGranted('ROLE_REFERENT')
            || $this->isGranted('ROLE_REFERENT_PARCOURS');
        $estFormateur = $this->isGranted('ROLE_FORMATEUR');
        $estApprenti = $this->isGranted('ROLE_APPRENTI');

        $estPersonnelAutorise =
            $estGestionnaire || $estReferent || $estFormateur;

        if (!$estPersonnelAutorise && !$estApprenti) {
            throw $this->createAccessDeniedException();
        }

        $utilisateur = $this->getUser();

        $campagneActive = $em->getRepository(Campagne::class)
            ->findOneBy(['active' => true], ['dateDebut' => 'DESC']);

        $estDansCampagneActive =
            $campagneActive !== null
            && $c->getCampagne() !== null
            && $c->getCampagne()->getId() === $campagneActive->getId();

        // Gestionnaire, Administrateur et Super administrateur :
        // accès à toutes les photos des candidats admis.
        $accesPhotoAutorise = $estGestionnaire;

        // Annuaire de la campagne active :
        // accès aux profils autorisés de l'annuaire.
        if (
            !$accesPhotoAutorise
            && $estDansCampagneActive
            && ($estReferent || $estFormateur)
        ) {
            $accesPhotoAutorise = true;
        }

        // Pour les anciennes campagnes, vérifier l'appartenance
        // de l'apprenti ou l'affectation active du personnel.
        if (!$accesPhotoAutorise && $utilisateur instanceof User) {
            if (
                $estApprenti
                && $utilisateur->getCandidat() !== null
                && $utilisateur->getCandidat()->getId() === $c->getId()
            ) {
                $accesPhotoAutorise = true;
            }

            if (!$accesPhotoAutorise && $estReferent) {
                $affectationReferent = $em
                    ->getRepository(AffectationResponsableApprenti::class)
                    ->findOneBy([
                        'candidat' => $c,
                        'responsable' => $utilisateur,
                        'typeResponsabilite' => AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS,
                        'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                        'dateFin' => null,
                    ]);

                $accesPhotoAutorise =
                    $affectationReferent instanceof AffectationResponsableApprenti;
            }

            if (!$accesPhotoAutorise && $estFormateur) {
                $affectationFormateur = $em
                    ->getRepository(AffectationResponsableApprenti::class)
                    ->findOneBy([
                        'candidat' => $c,
                        'responsable' => $utilisateur,
                        'typeResponsabilite' => AffectationResponsableApprenti::TYPE_FORMATEUR,
                        'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                        'dateFin' => null,
                    ]);

                $accesPhotoAutorise =
                    $affectationFormateur instanceof AffectationResponsableApprenti;
            }
        }

        if (!$accesPhotoAutorise) {
            throw $this->createNotFoundException('Photo indisponible.');
        }

        $admission = $em->getRepository(Admission::class)
            ->findOneBy(['candidat' => $c]);

        if (!$admission || $admission->getDecision() !== 'ADMIS') {
            throw $this->createNotFoundException('Photo indisponible.');
        }

        $nom = $c->getPhotoIdentite();

        if (
            !$nom
            || basename($nom) !== $nom
            || !preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $nom)
        ) {
            throw $this->createNotFoundException('Photo indisponible.');
        }

        $chemin = $this->getParameter('kernel.project_dir')
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'photos_identite'
            . DIRECTORY_SEPARATOR . $nom;

        if (!is_file($chemin)) {
            throw $this->createNotFoundException('Photo introuvable.');
        }

        $mime = str_ends_with($nom, '.png')
            ? 'image/png'
            : 'image/jpeg';

        $response = new BinaryFileResponse($chemin);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set(
            'Cache-Control',
            'private, no-store, no-cache, must-revalidate'
        );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'photo-identite-' . $c->getId() . '.' . pathinfo($nom, PATHINFO_EXTENSION)
        );

        return $response;
    }
    #[Route(
        '/candidats/{id}/photo-identite',
        name: 'candidat_photo_identite_upload',
        methods: ['POST']
    )]
    public function ajouterPhotoIdentite(
        Candidat $c,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $admission = $em->getRepository(Admission::class)
            ->findOneBy(['candidat' => $c]);

        if (!$admission || $admission->getDecision() !== 'ADMIS') {
            throw $this->createAccessDeniedException(
                "La photo ne peut etre ajoutee qu apres admission."
            );
        }

        if (!$this->isCsrfTokenValid(
            'photo_identite_' . $c->getId(),
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Formulaire expire. Veuillez reessayer.');
            return $this->redirectToRoute('candidat_show', ['id' => $c->getId()]);
        }

        $photo = $request->files->get('photo');

        if (!$photo instanceof UploadedFile || !$photo->isValid()) {
            $this->addFlash('danger', 'Veuillez selectionner une photo valide.');
            return $this->redirectToRoute('candidat_show', ['id' => $c->getId()]);
        }

        if ($photo->getSize() > 5 * 1024 * 1024) {
            $this->addFlash('danger', 'La photo ne doit pas depasser 5 Mo.');
            return $this->redirectToRoute('candidat_show', ['id' => $c->getId()]);
        }

        $mime = $photo->getMimeType();
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };

        if ($extension === null) {
            $this->addFlash('danger', 'Format refuse. Utilisez une image JPEG ou PNG.');
            return $this->redirectToRoute('candidat_show', ['id' => $c->getId()]);
        }

        $repertoire = $this->getParameter('kernel.project_dir')
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'photos_identite';

        if (!is_dir($repertoire) && !mkdir($repertoire, 0770, true) && !is_dir($repertoire)) {
            throw new \RuntimeException('Impossible de creer le repertoire des photos.');
        }

        $ancienNom = $c->getPhotoIdentite();
        $nouveauNom = bin2hex(random_bytes(16)) . '.' . $extension;

        try {
            $photo->move($repertoire, $nouveauNom);
            $c->setPhotoIdentite($nouveauNom);
            $em->flush();
        } catch (\Throwable $e) {
            $nouveauChemin = $repertoire . DIRECTORY_SEPARATOR . $nouveauNom;
            if (is_file($nouveauChemin)) {
                @unlink($nouveauChemin);
            }
            throw $e;
        }

        // Supprimer l'ancienne photo uniquement apres validation en base.
        if (
            $ancienNom
            && basename($ancienNom) === $ancienNom
            && preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $ancienNom)
        ) {
            $ancienChemin = $repertoire . DIRECTORY_SEPARATOR . $ancienNom;
            if (is_file($ancienChemin)) {
                @unlink($ancienChemin);
            }
        }

        $this->addFlash('success', 'Photo d identite enregistree avec succes.');

        return $this->redirectToRoute('candidat_show', ['id' => $c->getId()]);
    }
}