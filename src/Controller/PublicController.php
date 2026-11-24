<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Campagne;
use App\Service\CandidatService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

class PublicController extends AbstractController
{
    private const SESSION_KEY = 'candidature_en_cours';

    #[Route('/inscription', name: 'public_inscription', methods: ['GET', 'POST'])]
    public function inscription(Request $request): Response
    {
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'public_inscription_form',
                (string) $request->request->get('_token')
            )) {
                $this->addFlash(
                    'danger',
                    'Votre formulaire a expiré ou sa validation a échoué. Veuillez réessayer.'
                );

                return $this->redirectToRoute('public_inscription');
            }

    try {
                $data = $request->request->all('candidat');

                if (empty($data)) {
                    throw new \RuntimeException(
                        'Aucune donnée de candidature n’a été reçue.'
                    );
                }

                $required = [
                    'nom' => 'Le nom est obligatoire.',
                    'prenoms' => 'Les prénoms sont obligatoires.',
                    'sexe' => 'Le sexe est obligatoire.',
                    'dateNaissance' => 'La date de naissance est obligatoire.',
                    'nationalite' => 'La nationalité est obligatoire.',
                    'telephone' => 'Le téléphone est obligatoire.',
                    'pieceIdentite' => 'Le type de pièce d’identité est obligatoire.',
                    'niveau' => 'Le niveau est obligatoire.',
                    'diplome' => 'Le diplôme est obligatoire.',
                    'metier' => 'Le métier demandé est obligatoire.',
                    'disponibilite' => 'La disponibilité est obligatoire.',
                ];

                foreach ($required as $field => $message) {
                    if (
                        !isset($data[$field]) ||
                        trim((string) $data[$field]) === ''
                    ) {
                        throw new \RuntimeException($message);
                    }
                }

                // Nettoyage des données
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $data[$key] = trim($value);
                    }
                }

                // Adresse e-mail facultative
                $email = (string) ($data['email'] ?? '');

                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException(
                        'L adresse e-mail renseignée est invalide.'
                    );
                }

                $data['email'] = $email;

                // Vérification de la date
                $dateNaissance = \DateTime::createFromFormat(
                    'Y-m-d',
                    (string) $data['dateNaissance']
                );

                if (!$dateNaissance || $dateNaissance->format('Y-m-d') !== $data['dateNaissance']) {
                    throw new \RuntimeException(
                        'La date de naissance est invalide.'
                    );
                }

                // Récupération des fichiers
                $files = $request->files->all('candidat');

                $tempDir = $this->getParameter('kernel.project_dir')
                    . '/var/uploads/inscriptions/temp';

                if (
                    !is_dir($tempDir)
                    && !mkdir($tempDir, 0770, true)
                    && !is_dir($tempDir)
                ) {
                    throw new \RuntimeException(
                        'Impossible de créer le dossier temporaire privé.'
                    );
                }

                $documents = $session->get(self::SESSION_KEY . '_documents', []);

                foreach ([
                    'pieceIdentiteFichier',
                    'diplomeFichier'
                ] as $field) {
                    $uploadedFile = $files[$field] ?? null;

                    if (!$uploadedFile instanceof UploadedFile) {
                        continue;
                    }

                    if (!$uploadedFile->isValid()) {
                        throw new \RuntimeException(
                            'Le fichier "' . $field . '" n’a pas pu être téléchargé.'
                        );
                    }

                    if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
                        throw new \RuntimeException(
                            'Le fichier "' . $field . '" dépasse la taille maximale de 10 Mo.'
                        );
                    }

                    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                    $extension = strtolower(
                        $uploadedFile->guessExtension() ?: ''
                    );

                    if (!in_array($extension, $allowedExtensions, true)) {
                        throw new \RuntimeException(
                            'Le fichier "' . $field . '" doit être au format PDF, JPG, JPEG ou PNG.'
                        );
                    }

                    $filename = uniqid('candidature_', true) . '.' . $extension;

                    $uploadedFile->move($tempDir, $filename);

                    // Supprimer l'ancien fichier temporaire si nécessaire
                    if (
                        isset($documents[$field]) &&
                        is_file($tempDir . '/' . $documents[$field])
                    ) {
                        @unlink($tempDir . '/' . $documents[$field]);
                    }

                    $documents[$field] = $filename;
                }

                // Stockage temporaire en session
                $session->set(self::SESSION_KEY, $data);
                $session->set(self::SESSION_KEY . '_documents', $documents);

                return $this->redirectToRoute('public_inscription_recap');

            } catch (\Throwable $e) {
                $this->addFlash(
                    'danger',
                    'Votre candidature n’a pas pu être préparée.'
                );

                $this->addFlash(
                    'warning',
                    $e->getMessage()
                );

                $session->set(self::SESSION_KEY, $data ?? []);

                return $this->render('public/inscription.html.twig', [
                    'data' => $data ?? [],
                ]);
            }
        }

        $data = $session->get(self::SESSION_KEY, []);

        return $this->render('public/inscription.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route(
        '/inscription/recapitulatif',
        name: 'public_inscription_recap',
        methods: ['GET']
    )]
    public function recapitulatif(Request $request): Response
    {
        $session = $request->getSession();

        $data = $session->get(self::SESSION_KEY);

        if (!$data) {
            $this->addFlash(
                'warning',
                'Aucune candidature en cours de confirmation.'
            );

            return $this->redirectToRoute('public_inscription');
        }

        $documents = $session->get(
            self::SESSION_KEY . '_documents',
            []
        );

        return $this->render('public/recapitulatif.html.twig', [
            'data' => $data,
            'documents' => $documents,
        ]);
    }

    #[Route(
        '/inscription/confirmer',
        name: 'public_inscription_confirmer',
        methods: ['POST']
    )]
    public function confirmer(
        Request $request,
        EntityManagerInterface $em,
        CandidatService $svc,
        LoggerInterface $logger
    ): Response {
        $session = $request->getSession();

        if (!$this->isCsrfTokenValid(
            'public_inscription_confirmer',
            (string) $request->request->get('_token')
        )) {
            $this->addFlash(
                'danger',
                'La validation de votre candidature a échoué. Veuillez réessayer.'
            );

            return $this->redirectToRoute('public_inscription_recap');
        }

        $data = $session->get(self::SESSION_KEY);

        if (!$data) {
            $this->addFlash(
                'danger',
                'Aucune candidature à confirmer.'
            );

            return $this->redirectToRoute('public_inscription');
        }

        $documents = $session->get(
            self::SESSION_KEY . '_documents',
            []
        );

        try {
            $candidat = new Candidat();

            // Données texte
            $fields = [
                'nom',
                'prenoms',
                'sexe',
                'lieuNaissance',
                'lieuResidence',
                'nationalite',
                'telephone',
                'telephoneAlt',
                'email',
                'pieceIdentite',
                'niveau',
                'diplome',
                'metier',
                'ancienMetier',
                'disponibilite',
                'contactUrgenceNom',
                'contactUrgenceTel',
                'contactUrgenceLien',
                'experience',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $method = 'set' . ucfirst($field);

                    if (!method_exists($candidat, $method)) {
                        throw new \RuntimeException(
                            'La méthode ' . $method . '() est absente de l’entité Candidat.'
                        );
                    }

                    $value = $data[$field];

                    if ($value === '') {
                        $value = null;
                    }

                    $candidat->$method($value);
                }
            }

            // Date de naissance
            if (!empty($data['dateNaissance'])) {
                $date = \DateTime::createFromFormat(
                    'Y-m-d',
                    $data['dateNaissance']
                );

                if (!$date) {
                    throw new \RuntimeException(
                        'La date de naissance est invalide.'
                    );
                }

                $candidat->setDateNaissance($date);
            }

            // Documents : stockage hors du dossier public
            $tempDir = $this->getParameter('kernel.project_dir')
                . '/var/uploads/inscriptions/temp';

            $finalDir = $this->getParameter('kernel.project_dir')
                . '/var/uploads/inscriptions/candidats';

            if (
                !is_dir($finalDir)
                && !mkdir($finalDir, 0770, true)
                && !is_dir($finalDir)
            ) {
                throw new \RuntimeException(
                    'Impossible de créer le dossier privé des candidats.'
                );
            }

            foreach ([
                'pieceIdentiteFichier',
                'diplomeFichier'
            ] as $field) {
                if (empty($documents[$field])) {
                    continue;
                }

                $tempFilename = basename($documents[$field]);
                $tempFile = $tempDir . DIRECTORY_SEPARATOR . $tempFilename;

                if (!is_file($tempFile)) {
                    throw new \RuntimeException(
                        'Un document temporaire est introuvable : ' . $field
                    );
                }

                $finalFilename = bin2hex(random_bytes(16))
                    . '_' . $tempFilename;

                $destination = $finalDir
                    . DIRECTORY_SEPARATOR . $finalFilename;

                if (!copy($tempFile, $destination)) {
                    throw new \RuntimeException(
                        'Impossible de copier le document : ' . $field
                    );
                }

                $setter = 'set' . ucfirst($field);

                if (!method_exists($candidat, $setter)) {
                    @unlink($destination);

                    throw new \RuntimeException(
                        'Méthode absente : ' . $setter
                    );
                }

                $candidat->$setter($finalFilename);
            }

            $eligibilite = $svc->estEligible($candidat);

            if (!is_array($eligibilite)) {
                throw new \RuntimeException(
                    'Le service d’éligibilité a retourné une réponse invalide.'
                );
            }

            $candidat->setStatut(
                !empty($eligibilite['eligible'])
                    ? 'ELIGIBLE'
                    : 'A_VERIFIER'
            );

            // Campagne active
            $campagne = $em
                ->getRepository(Campagne::class)
                ->findOneBy(['active' => true]);

            if (!$campagne) {
                throw new \RuntimeException(
                    'Aucune campagne de recrutement active n’est configurée.'
                );
            }

            $candidat->setCampagne($campagne);

            // Enregistrement définitif
            $em->persist($candidat);
            $em->flush();

            // Les pièces temporaires ne sont supprimées
            // qu'après confirmation de l'enregistrement en base.
            foreach ([
                'pieceIdentiteFichier',
                'diplomeFichier'
            ] as $field) {
                if (!empty($documents[$field])) {
                    $temporaryFile = $tempDir
                        . DIRECTORY_SEPARATOR
                        . basename($documents[$field]);

                    if (is_file($temporaryFile)) {
                        @unlink($temporaryFile);
                    }
                }
            }

            // Nettoyage session
            $session->remove(self::SESSION_KEY);
            $session->remove(self::SESSION_KEY . '_documents');

            return $this->render('public/success.html.twig', [
                'candidat' => $candidat,
                'eligibilite' => $eligibilite,
            ]);

        } catch (\Throwable $e) {

            $logger->error(
                'Erreur lors de la confirmation de candidature.',
                [
                    'exception' => $e,
                ]
            );

            $this->addFlash(
                'danger',
                'La candidature n’a pas pu être enregistrée.'
            );

            $this->addFlash(
                'warning',
                'Motif : ' . $e->getMessage()
            );

            return $this->redirectToRoute(
                'public_inscription_recap'
            );
        }
    }
}