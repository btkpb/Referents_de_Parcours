<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Entretien;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

final class ConvocationPdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly KernelInterface $kernel
    ) {
    }

    public function generer(Entretien $entretien): string
    {
        $projectDir = $this->kernel->getProjectDir();

        $dossier = $projectDir . '/var/convocations';
        $cacheDir = $projectDir . '/var/cache/dompdf';

        foreach ([$dossier, $cacheDir] as $directory) {
            if (
                !is_dir($directory)
                && !mkdir($directory, 0770, true)
                && !is_dir($directory)
            ) {
                throw new \RuntimeException(
                    'Impossible de créer le répertoire privé des convocations.'
                );
            }
        }

        $logoPath = $projectDir . '/public/images/logo DAIP.png';
        $logoDataUri = null;

        if (is_file($logoPath) && is_readable($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode(
                (string) file_get_contents($logoPath)
            );
        }

        $html = $this->twig->render(
            'recrutement/convocation_pdf.html.twig',
            [
                'entretien' => $entretien,
                'candidat' => $entretien->getCandidat(),
                'logoDataUri' => $logoDataUri,
                'dateEdition' => new \DateTimeImmutable(),
            ]
        );

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', $projectDir);
        $options->set('tempDir', $cacheDir);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nomFichier = sprintf(
            'convocation_entretien_%d_%s_%s.pdf',
            $entretien->getId(),
            date('Ymd_His'),
            bin2hex(random_bytes(4))
        );

        $chemin = $dossier . '/' . $nomFichier;
        $resultat = file_put_contents($chemin, $dompdf->output(), LOCK_EX);

        if ($resultat === false || !is_file($chemin)) {
            throw new \RuntimeException(
                'La génération du fichier PDF a échoué.'
            );
        }

        return $chemin;
    }
}