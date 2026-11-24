<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\VisiteMedicale;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

class MedicalController extends AbstractController
{
    private const MAX_FILE_SIZE = 10485760;

    private function assertMedicalAccess(): void
    {
        if (
            !$this->isGranted('ROLE_GESTIONNAIRE')
            && !$this->isGranted('ROLE_MEDICAL')
        ) {
            throw $this->createAccessDeniedException(
                'Accès réservé au Gestionnaire et au Personnel médical.'
            );
        }
    }

    private function assertMedicalStaff(): void
    {
        $rolesDirects = $this->getUser()?->getRoles() ?? [];

        if (!in_array('ROLE_MEDICAL', $rolesDirects, true)) {
            throw $this->createAccessDeniedException(
                'Seul le Personnel médical peut enregistrer une décision médicale.'
            );
        }
    }

    #[Route('/medical', name: 'medical_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->assertMedicalAccess();

        $page = max(1, $request->query->getInt('page', 1));
        $limite = 20;

        $recherche = trim((string) $request->query->get('recherche', ''));
        $aptitudeFiltre = strtoupper(
            trim((string) $request->query->get('aptitude', ''))
        );

        if (!in_array($aptitudeFiltre, ['APTE', 'INAPTE', 'EN_ATTENTE'], true)) {
            $aptitudeFiltre = '';
        }

        $qb = $em->getRepository(VisiteMedicale::class)
            ->createQueryBuilder('v')
            ->leftJoin('v.candidat', 'c')
            ->addSelect('c');

        if ($recherche !== '') {
            $qb
                ->andWhere(
                    'LOWER(c.nom) LIKE :recherche OR LOWER(c.prenoms) LIKE :recherche'
                )
                ->setParameter(
                    'recherche',
                    '%' . mb_strtolower($recherche) . '%'
                );
        }

        if ($aptitudeFiltre !== '') {
            $qb
                ->andWhere('v.aptitude = :aptitude')
                ->setParameter('aptitude', $aptitudeFiltre);
        }

        $requeteComptage = clone $qb;
        $total = (int) $requeteComptage
            ->resetDQLPart('orderBy')
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $nombrePages = max(1, (int) ceil($total / $limite));
        $page = min($page, $nombrePages);

        $items = $qb
            ->orderBy('v.dateHeure', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();

        return $this->render('medical/index.html.twig', [
            'items' => $items,
            'candidats' => $em->getRepository(Candidat::class)
                ->findBy([], ['nom' => 'ASC']),
            'isMedicalStaff' => in_array(
                'ROLE_MEDICAL',
                $this->getUser()?->getRoles() ?? [],
                true
            ),
            'recherche' => $recherche,
            'aptitudeFiltre' => $aptitudeFiltre,
            'page' => $page,
            'nombrePages' => $nombrePages,
            'limite' => $limite,
            'total' => $total,
        ]);
    }
    #[Route('/medical/nouvelle', name: 'medical_nouvelle', methods: ['POST'])]
    public function nouvelle(
        Request $request,
        EntityManagerInterface $em,
        \Psr\Log\LoggerInterface $logger
    ): Response {
        $this->assertMedicalAccess();

        if (!$this->isCsrfTokenValid(
            'medical_nouvelle',
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        try {
            $candidatId = filter_var(
                $request->request->get('candidat'),
                FILTER_VALIDATE_INT
            );

            $candidat = $candidatId
                ? $em->getRepository(Candidat::class)->find($candidatId)
                : null;

            if (!$candidat) {
                throw new \InvalidArgumentException(
                    'Veuillez sélectionner un candidat valide.'
                );
            }

            $centre = trim((string) $request->request->get('centre'));

            if ($centre === '' || mb_strlen($centre) > 180) {
                throw new \InvalidArgumentException(
                    'Le centre médical est obligatoire (180 caractères maximum).'
                );
            }

            $dateSaisie = (string) $request->request->get('dateHeure');
            $date = \DateTime::createFromFormat(
                '!Y-m-d\TH:i',
                $dateSaisie
            );

            if (!$date || $date->format('Y-m-d\TH:i') !== $dateSaisie) {
                throw new \InvalidArgumentException(
                    'Veuillez renseigner une date et une heure valides.'
                );
            }

            // Vérifier qu'une visite identique n'existe pas déjà.
            $visiteExistante = $em->getRepository(VisiteMedicale::class)->findOneBy([
                'candidat' => $candidat,
                'dateHeure' => $date,
            ]);

            if ($visiteExistante !== null) {
                throw new \InvalidArgumentException(
                    'Une visite médicale est déjà planifiée pour ce candidat à cette date et cette heure.'
                );
            }

            $visite = new VisiteMedicale();
            $visite->setCandidat($candidat);
            $visite->setDateHeure($date);
            $visite->setCentre($centre);

            // Une visite planifiée reste obligatoirement en attente.
            $visite->setAptitude('EN_ATTENTE');

            $observationsPlanification = trim(
                (string) $request->request->get('observations_planification')
            );
            $visite->setObservationsPlanification(
                $observationsPlanification !== '' ? $observationsPlanification : null
            );

            $em->persist($visite);
            $em->flush();

            $this->addFlash(
                'success',
                'La visite médicale a été planifiée avec le statut En attente.'
            );

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());

        } catch (\Throwable $e) {
            $logger->error(
                'Erreur lors de la planification d’une visite médicale.',
                ['exception' => $e]
            );
            $this->addFlash(
                'danger',
                'Une erreur est survenue lors de la planification de la visite médicale.'
            );
        }

        return $this->redirectToRoute('medical_index');
    }

    #[Route(
        '/medical/{id}/resultat',
        name: 'medical_resultat',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function resultat(
        VisiteMedicale $visite,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->assertMedicalStaff();

        if (!$this->isCsrfTokenValid(
            'medical_resultat_' . $visite->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        $fichiersDeplaces = [];

        try {
            $aptitude = (string) $request->request->get('aptitude');

            if (!in_array($aptitude, ['APTE', 'INAPTE'], true)) {
                throw new \InvalidArgumentException(
                    'Veuillez sélectionner une décision médicale valide.'
                );
            }

            $certificat = $request->files->get('certificat');
            $photo = $request->files->get('photo');

            $certificatExtension = $this->validateUpload(
                $certificat,
                ['application/pdf'],
                'Le certificat médical doit être un fichier PDF.'
            );

            $photoExtension = $this->validateUpload(
                $photo,
                ['image/jpeg', 'image/png'],
                'La photo médicale doit être au format JPG, JPEG ou PNG.'
            );

            // Un certificat existant peut être conservé lors d'une modification.
            if (!$visite->getCertificat() && !$certificat instanceof UploadedFile) {
                throw new \InvalidArgumentException(
                    'Le certificat médical PDF est obligatoire pour valider la décision.'
                );
            }

            $dossier = $this->getParameter('kernel.project_dir')
                . '/var/uploads/medical';

            if (
                !is_dir($dossier)
                && !mkdir($dossier, 0770, true)
                && !is_dir($dossier)
            ) {
                throw new \RuntimeException(
                    'Impossible de créer le dossier médical privé.'
                );
            }

            $ancienCertificat = $visite->getCertificat();
            $anciennePhoto = $visite->getPhoto();

            if (
                $certificat instanceof UploadedFile
                && $certificatExtension !== null
            ) {
                $nom = bin2hex(random_bytes(24)) . '.' . $certificatExtension;
                $certificat->move($dossier, $nom);
                $fichiersDeplaces[] = $dossier . '/' . $nom;
                $visite->setCertificat($nom);
            }

            if ($photo instanceof UploadedFile && $photoExtension !== null) {
                $nom = bin2hex(random_bytes(24)) . '.' . $photoExtension;
                $photo->move($dossier, $nom);
                $fichiersDeplaces[] = $dossier . '/' . $nom;
                $visite->setPhoto($nom);
            }

            $visite->setAptitude($aptitude);

            $observations = trim(
                (string) $request->request->get('observations')
            );

            $visite->setObservations(
                $observations !== ''
                    ? $observations
                    : $visite->getObservations()
            );

            $em->flush();

            // Suppression des anciens fichiers remplacés après validation en base.
            if (
                $ancienCertificat
                && $ancienCertificat !== $visite->getCertificat()
            ) {
                $ancienChemin = $dossier . '/' . basename($ancienCertificat);
                if (is_file($ancienChemin)) {
                    @unlink($ancienChemin);
                }
            }

            if (
                $anciennePhoto
                && $anciennePhoto !== $visite->getPhoto()
            ) {
                $ancienChemin = $dossier . '/' . basename($anciennePhoto);
                if (is_file($ancienChemin)) {
                    @unlink($ancienChemin);
                }
            }

            $this->addFlash(
                'success',
                'La décision médicale et les documents ont été enregistrés.'
            );

        } catch (\InvalidArgumentException $e) {
            foreach ($fichiersDeplaces as $fichier) {
                if (is_file($fichier)) {
                    @unlink($fichier);
                }
            }

            $this->addFlash('danger', $e->getMessage());

        } catch (\Throwable $e) {
            foreach ($fichiersDeplaces as $fichier) {
                if (is_file($fichier)) {
                    @unlink($fichier);
                }
            }

            $this->addFlash(
                'danger',
                'Une erreur est survenue lors de l’enregistrement du résultat médical.'
            );
        }

        return $this->redirectToRoute('medical_index');
    }

    private function validateUpload(
        ?UploadedFile $file,
        array $mimeAutorises,
        string $messageErreur
    ): ?string {
        if ($file === null) {
            return null;
        }

        if (!$file->isValid()) {
            throw new \InvalidArgumentException(
                'Un fichier transmis est invalide.'
            );
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                'Chaque fichier doit faire 10 Mo maximum.'
            );
        }

        $mime = $file->getMimeType();

        if (!in_array($mime, $mimeAutorises, true)) {
            throw new \InvalidArgumentException($messageErreur);
        }

        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new \InvalidArgumentException($messageErreur),
        };
    }

    #[Route(
        '/medical/{id}/document/{type}',
        name: 'medical_document_download',
        requirements: ['id' => '\d+', 'type' => 'certificat|photo'],
        methods: ['GET']
    )]
    public function telecharger(
        VisiteMedicale $visite,
        string $type
    ): BinaryFileResponse {
        $this->assertMedicalAccess();

        $nomFichier = match ($type) {
            'certificat' => $visite->getCertificat(),
            'photo' => $visite->getPhoto(),
            default => null,
        };

        if (!$nomFichier || basename($nomFichier) !== $nomFichier) {
            throw $this->createNotFoundException(
                'Document médical introuvable.'
            );
        }

        $chemin = $this->getParameter('kernel.project_dir')
            . '/var/uploads/medical/'
            . $nomFichier;

        if (!is_file($chemin)) {
            throw $this->createNotFoundException(
                'Le fichier médical est introuvable.'
            );
        }

        $mime = $type === 'certificat'
            ? 'application/pdf'
            : match (strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                default => 'application/octet-stream',
            };

        $response = new BinaryFileResponse($chemin);
        $response->headers->set('Content-Type', $mime);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set(
            'Cache-Control',
            'private, no-store, no-cache, must-revalidate'
        );
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($nomFichier)
        );

        return $response;
    }
}
