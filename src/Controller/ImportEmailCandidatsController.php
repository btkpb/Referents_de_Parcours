<?php

namespace App\Controller;

use App\Entity\Candidat;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class ImportEmailCandidatsController extends AbstractController
{
    private const SESSION_KEY = 'import_emails_candidats_preview';
    private const MAX_FILE_SIZE = 5242880;
    private const MAX_ROWS = 5000;

    #[Route('/candidats/import-emails', name: 'candidat_import_emails', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $erreurs = [];

        if ($request->isMethod('POST')) {
            $request->getSession()->remove(self::SESSION_KEY);

            if (!$this->isCsrfTokenValid(
                'import_emails_candidats',
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
            }

            $fichier = $request->files->get('fichier');

            if (!$fichier) {
                $erreurs[] = 'Veuillez sélectionner un fichier.';
            } elseif (!$fichier->isValid()) {
                $erreurs[] = 'Le fichier transmis est invalide.';
            } elseif (($fichier->getSize() ?? 0) > self::MAX_FILE_SIZE) {
                $erreurs[] = 'Le fichier ne doit pas dépasser 5 Mo.';
            } else {
                $extension = strtolower($fichier->getClientOriginalExtension());

                if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                    $erreurs[] = 'Format non accepté. Utilisez Excel (.xlsx, .xls) ou CSV (.csv).';
                } else {
                    try {
                        $reader = IOFactory::createReaderForFile($fichier->getPathname());

                        if ($extension === 'csv' && method_exists($reader, 'setDelimiter')) {
                            $premiereLigne = file($fichier->getPathname(), FILE_IGNORE_NEW_LINES)[0] ?? '';
                            $delimiter = substr_count($premiereLigne, ';') > substr_count($premiereLigne, ',')
                                ? ';'
                                : ',';

                            $reader->setDelimiter($delimiter);
                            $reader->setEnclosure('"');
                            $reader->setInputEncoding('UTF-8');
                        }

                        $reader->setReadDataOnly(true);
                        $classeur = $reader->load($fichier->getPathname());
                        $feuille = $classeur->getActiveSheet();
                        $donnees = $feuille->toArray(null, true, true, false);
                        $classeur->disconnectWorksheets();

                        if (count($donnees) < 2) {
                            $erreurs[] = 'Le fichier ne contient aucune ligne de candidat.';
                        } elseif (count($donnees) - 1 > self::MAX_ROWS) {
                            $erreurs[] = 'Le fichier dépasse la limite de 5 000 lignes.';
                        } else {
                            $entetes = array_map(
                                static fn ($v) => strtolower(trim((string) $v)),
                                $donnees[0]
                            );

                            $colNumero = array_search('numero_candidat', $entetes, true);
                            $colEmail = array_search('email', $entetes, true);

                            if ($colNumero === false || $colEmail === false) {
                                $erreurs[] = 'Les deux colonnes obligatoires doivent être présentes : numero_candidat et email.';
                            } else {
                                $lignes = [];
                                $comptes = [];
                                $numeros = [];

                                foreach (array_slice($donnees, 1, null, true) as $index => $ligne) {
                                    $numero = trim((string) ($ligne[$colNumero] ?? ''));
                                    $email = trim((string) ($ligne[$colEmail] ?? ''));

                                    if ($numero === '' && $email === '') {
                                        continue;
                                    }

                                    $numeroLigne = $index + 1;
                                    $lignes[] = [
                                        'ligne' => $numeroLigne,
                                        'numero' => $numero,
                                        'email' => $email,
                                    ];

                                    if ($numero !== '') {
                                        $comptes[$numero] = ($comptes[$numero] ?? 0) + 1;
                                        $numeros[] = $numero;
                                    }
                                }

                                $numeros = array_values(array_unique($numeros));

                                $candidats = $numeros
                                    ? $em->getRepository(Candidat::class)->findBy(['numero' => $numeros])
                                    : [];

                                $parNumero = [];
                                foreach ($candidats as $candidat) {
                                    $parNumero[$candidat->getNumero()] = $candidat;
                                }

                                $lignesValides = [];

                                foreach ($lignes as $ligne) {
                                    $numero = $ligne['numero'];
                                    $email = $ligne['email'];
                                    $motif = null;

                                    if ($numero === '') {
                                        $motif = 'Numéro de candidat manquant.';
                                    } elseif (($comptes[$numero] ?? 0) > 1) {
                                        $motif = 'Numéro de candidat présent plusieurs fois dans le fichier.';
                                    } elseif (!isset($parNumero[$numero])) {
                                        $motif = 'Numéro de candidat introuvable.';
                                    } elseif ($email !== '' && mb_strlen($email) > 180) {
                                        $motif = 'Adresse e-mail supérieure à 180 caractères.';
                                    } elseif ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                                        $motif = 'Adresse e-mail invalide.';
                                    }

                                    if ($motif !== null) {
                                        $erreurs[] = 'Ligne ' . $ligne['ligne'] . ' : ' . $motif;
                                        continue;
                                    }

                                    $candidat = $parNumero[$numero];
                                    $nouvelEmail = $email !== '' ? $email : null;

                                    $lignesValides[] = [
                                        'ligne' => $ligne['ligne'],
                                        'numero' => $numero,
                                        'email' => $nouvelEmail,
                                        'action' => $nouvelEmail === null
                                            ? 'Suppression de l’adresse existante'
                                            : ($candidat->getEmail() === $nouvelEmail
                                                ? 'Adresse inchangée'
                                                : 'Mise à jour'),
                                    ];
                                }

                                if ($erreurs === [] && $lignesValides !== []) {
                                    $request->getSession()->set(self::SESSION_KEY, $lignesValides);
                                } elseif ($erreurs === [] && $lignesValides === []) {
                                    $erreurs[] = 'Aucune ligne exploitable dans le fichier.';
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        $erreurs[] = 'Impossible de lire le fichier. Vérifiez son format et son contenu.';
                    }
                }
            }
        }

        return $this->render('candidat/import_emails.html.twig', [
            'erreurs' => $erreurs,
            'lignes' => $request->getSession()->get(self::SESSION_KEY, []),
        ]);
    }

    #[Route('/candidats/import-emails/modele', name: 'candidat_import_emails_modele', methods: ['GET'])]
    public function modele(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $classeur = new Spreadsheet();
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle('Import emails');

        $feuille->setCellValue('A1', 'numero_candidat');
        $feuille->setCellValue('B1', 'email');

        $feuille->getStyle('A1:B1')->getFont()->setBold(true);
        $feuille->getColumnDimension('A')->setWidth(32);
        $feuille->getColumnDimension('B')->setWidth(42);

        return new StreamedResponse(static function () use ($classeur): void {
            $writer = new Xlsx($classeur);
            $writer->save('php://output');
            $classeur->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="modele_import_emails_candidats.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    #[Route('/candidats/import-emails/confirmer', name: 'candidat_import_emails_confirmer', methods: ['POST'])]
    public function confirmer(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        if (!$this->isCsrfTokenValid(
            'confirmer_import_emails_candidats',
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $lignes = $request->getSession()->get(self::SESSION_KEY, []);

        if (!is_array($lignes) || $lignes === []) {
            $this->addFlash('warning', 'Aucun import en attente de confirmation.');
            return $this->redirectToRoute('candidat_import_emails');
        }

        $contientSuppressions = false;

        foreach ($lignes as $ligne) {
            if (
                !is_array($ligne)
                || !array_key_exists('numero', $ligne)
                || !array_key_exists('email', $ligne)
            ) {
                $request->getSession()->remove(self::SESSION_KEY);
                $this->addFlash('danger', 'Aperçu invalide. Veuillez recommencer l’importation.');
                return $this->redirectToRoute('candidat_import_emails');
            }

            if ($ligne['email'] === null) {
                $contientSuppressions = true;
            }
        }

        if (
            $contientSuppressions
            && $request->request->get('autoriser_suppressions') !== '1'
        ) {
            $this->addFlash(
                'warning',
                'Confirmation refusée : vous devez autoriser explicitement la suppression des adresses e-mail vides.'
            );

            return $this->redirectToRoute('candidat_import_emails');
        }

        $numeros = array_values(array_unique(array_column($lignes, 'numero')));
        $candidats = $em->getRepository(Candidat::class)->findBy(['numero' => $numeros]);

        $parNumero = [];
        foreach ($candidats as $candidat) {
            $parNumero[$candidat->getNumero()] = $candidat;
        }

        $connexion = $em->getConnection();

        try {
            $connexion->beginTransaction();

            foreach ($lignes as $ligne) {
                $numero = $ligne['numero'];

                if (!isset($parNumero[$numero])) {
                    throw new \RuntimeException('Un candidat n’existe plus. Import annulé.');
                }

                $parNumero[$numero]->setEmail($ligne['email']);
            }

            $em->flush();
            $connexion->commit();

            $request->getSession()->remove(self::SESSION_KEY);

            $this->addFlash(
                'success',
                count($lignes) . ' ligne(s) traitée(s). Seules les adresses e-mail ont été modifiées.'
            );
        } catch (\Throwable $e) {
            if ($connexion->isTransactionActive()) {
                $connexion->rollBack();
            }

            $this->addFlash('danger', 'Import annulé : aucune modification n’a été validée.');
        }

        return $this->redirectToRoute('candidat_import_emails');
    }
}