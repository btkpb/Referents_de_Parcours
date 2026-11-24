<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ConvocationEnvoi;
use App\Entity\Entretien;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class ConvocationEmailService
{
    private const EXPEDITEUR = 'bakari_parfait@yahoo.fr';
    private const NOM_EXPEDITEUR_DEFAUT = 'Admin Référent de Parcours Plateforme';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $db,
        private readonly MailerInterface $mailer,
        private readonly ConvocationPdfService $pdfService
    ) {
    }

    /**
     * Chaque appel crée une nouvelle tentative historisée.
     */
    public function envoyer(Entretien $entretien): ConvocationEnvoi
    {
        $candidat = $entretien->getCandidat();

        if ($candidat === null) {
            throw new \RuntimeException(
                'Impossible de créer une convocation sans candidat associé.'
            );
        }

        $adresse = trim((string) $candidat->getEmail());

        $envoi = (new ConvocationEnvoi())
            ->setEntretien($entretien)
            ->setCandidat($candidat)
            ->setCanal('EMAIL')
            ->setDestinataire($adresse)
            ->setStatut('EN_ATTENTE');

        $this->em->persist($envoi);
        $this->em->flush();

        if ($adresse === '') {
            $envoi
                ->setStatut('SANS_EMAIL')
                ->setErreur(
                    'Aucune adresse e-mail n’est renseignée pour ce candidat.'
                );

            $this->em->flush();

            return $envoi;
        }

        if (filter_var($adresse, FILTER_VALIDATE_EMAIL) === false) {
            $envoi
                ->setStatut('ECHEC')
                ->setErreur(
                    'L’adresse e-mail du candidat n’est pas valide.'
                );

            $this->em->flush();

            return $envoi;
        }

        $cheminPdf = null;

        try {
            $cheminPdf = $this->pdfService->generer($entretien);

            if (!is_file($cheminPdf) || !is_readable($cheminPdf)) {
                throw new \RuntimeException(
                    'Le fichier PDF de convocation est introuvable ou illisible.'
                );
            }

            $numeroEntretien = (string) $entretien->getId();

            $nomExpediteur = trim((string) $this->db->fetchOne(
                'SELECT valeur FROM configuration_email WHERE cle = :cle',
                ['cle' => 'nom_expediteur_convocation']
            ));

            if ($nomExpediteur === '') {
                $nomExpediteur = self::NOM_EXPEDITEUR_DEFAUT;
            }

            $email = (new TemplatedEmail())
                ->from(new Address(
                    self::EXPEDITEUR,
                    $nomExpediteur
                ))
                ->to($adresse)
                ->subject('Convocation à votre entretien de sélection E2C')
                ->htmlTemplate('recrutement/email_convocation.html.twig')
                ->context([
                    'entretien' => $entretien,
                    'candidat' => $candidat,
                ])
                ->attachFromPath(
                    $cheminPdf,
                    'Convocation_E2C_' . $numeroEntretien . '.pdf',
                    'application/pdf'
                );

            // Cette instruction correspond à la tentative de transport.
            $this->mailer->send($email);

        } catch (\Throwable $exception) {
            // Seules les erreurs survenues avant ou pendant le transport
            // sont classées comme échecs d'envoi.
            $envoi
                ->setStatut('ECHEC')
                ->setErreur(substr($exception->getMessage(), 0, 60000));

            // Si Doctrine ne peut pas enregistrer l'échec, l'exception
            // remonte au contrôleur au lieu de masquer le problème.
            $this->em->flush();

            return $envoi;

        } finally {
            // Suppression uniquement du PDF temporaire généré pour cet envoi.
            if (
                $cheminPdf !== null
                && is_file($cheminPdf)
                && str_contains(
                    str_replace('\\', '/', $cheminPdf),
                    '/var/convocations/'
                )
            ) {
                @unlink($cheminPdf);
            }
        }

        // Le transport a accepté le message.
        // La persistance de ce succès est volontairement hors du catch SMTP :
        // une erreur Doctrine ici ne doit jamais être présentée comme un
        // échec certain de transmission du message.
        $dateEnvoi = new \DateTimeImmutable();

        $envoi
            ->setStatut('ENVOYE')
            ->setErreur(null)
            ->setDateEnvoi($dateEnvoi);

        $entretien->setConvocationEnvoyeeAt(
            $dateEnvoi->format('Y-m-d H:i:s')
        );

        $this->em->flush();

        return $envoi;
    }
}