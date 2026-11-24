<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class EmailInformationService
{
    private const EXPEDITEUR = 'bakari_parfait@yahoo.fr';
    private const NOM_EXPEDITEUR_DEFAUT = 'Admin Référent de Parcours Plateforme';

    public function __construct(
        private readonly Connection $db,
        private readonly MailerInterface $mailer
    ) {
    }

    public function campagnes(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT id, nom FROM campagne ORDER BY nom ASC'
        );
    }

    public function metiers(): array
    {
        return $this->db->fetchFirstColumn(
            "SELECT DISTINCT metier
             FROM candidat
             WHERE metier IS NOT NULL AND TRIM(metier) <> ''
             ORDER BY metier ASC"
        );
    }

    public function lister(string $audience, array $filtres = []): array
    {
        $destinataires = $this->chargerAudience($audience);

        return array_values(array_filter(
            $destinataires,
            static function (array $destinataire) use ($filtres): bool {
                if (
                    !empty($filtres['campagne_id'])
                    && (int) ($destinataire['campagne_id'] ?? 0)
                        !== (int) $filtres['campagne_id']
                ) {
                    return false;
                }

                if (
                    !empty($filtres['metier'])
                    && (string) ($destinataire['metier'] ?? '')
                        !== (string) $filtres['metier']
                ) {
                    return false;
                }

                if (
                    !empty($filtres['statut'])
                    && (string) ($destinataire['statut'] ?? '')
                        !== (string) $filtres['statut']
                ) {
                    return false;
                }

                return true;
            }
        ));
    }

    public function selectionner(string $audience, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(
                static fn ($id) => filter_var($id, FILTER_VALIDATE_INT),
                $ids
            ),
            static fn ($id) => $id !== false && $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        return array_values(array_filter(
            $this->chargerAudience($audience),
            static fn (array $destinataire): bool =>
                in_array((int) $destinataire['destinataire_id'], $ids, true)
        ));
    }

    private function chargerAudience(string $audience): array
    {
        if ($audience === 'CANDIDAT') {
            $rows = $this->db->fetchAllAssociative(
                'SELECT
                    c.id AS destinataire_id,
                    c.nom,
                    c.prenoms,
                    c.email,
                    c.numero,
                    c.metier,
                    CASE WHEN a.id IS NOT NULL THEN a.decision ELSE c.statut END AS statut,
                    c.campagne_id,
                    ca.nom AS campagne
                 FROM candidat c
             LEFT JOIN admission a ON a.candidat_id = c.id
                 LEFT JOIN campagne ca ON ca.id = c.campagne_id
                 ORDER BY c.nom ASC, c.prenoms ASC'
            );
        } elseif ($audience === 'APPRENTI') {
            $rows = $this->db->fetchAllAssociative(
                "SELECT
                    u.id AS destinataire_id,
                    u.nom,
                    u.prenoms,
                    u.email,
                    u.roles,
                    c.numero,
                    c.metier,
                    CASE WHEN a.id IS NOT NULL THEN a.decision ELSE c.statut END AS statut,
                    c.campagne_id,
                    ca.nom AS campagne
                 FROM app_user u
                 INNER JOIN candidat c ON c.id = u.candidat_id
             LEFT JOIN admission a ON a.candidat_id = c.id
                 LEFT JOIN campagne ca ON ca.id = c.campagne_id
                 WHERE u.active = 1 AND u.profil = 'apprenti'
                 ORDER BY u.nom ASC, u.prenoms ASC"
            );

            $rows = array_values(array_filter(
                $rows,
                static function (array $row): bool {
                    $roles = json_decode((string) ($row['roles'] ?? '[]'), true);

                    return is_array($roles)
                        && in_array('ROLE_APPRENTI', $roles, true);
                }
            ));
        } else {
            throw new \InvalidArgumentException('Audience non reconnue.');
        }

        foreach ($rows as &$row) {
            $row['email'] = trim((string) ($row['email'] ?? ''));
            $row['nom_complet'] = trim(
                (string) ($row['nom'] ?? '') . ' '
                . (string) ($row['prenoms'] ?? '')
            );
            $row['email_valide'] = filter_var(
                $row['email'],
                FILTER_VALIDATE_EMAIL
            ) !== false;
            unset($row['roles']);
        }
        unset($row);

        return $rows;
    }

    public function envoyer(
        string $audience,
        array $ids,
        string $sujet,
        string $contenu
    ): array {
        $destinataires = $this->selectionner($audience, $ids);
        $nomExpediteur = trim((string) $this->db->fetchOne(
            'SELECT valeur FROM configuration_email WHERE cle = :cle',
            ['cle' => 'nom_expediteur_convocation']
        ));

        if ($nomExpediteur === '') {
            $nomExpediteur = self::NOM_EXPEDITEUR_DEFAUT;
        }

        $resultat = [
            'envoyes' => 0,
            'echecs' => 0,
            'exclus' => 0,
        ];

        foreach ($destinataires as $destinataire) {
            if (!$destinataire['email_valide']) {
                ++$resultat['exclus'];
                continue;
            }

            $dateTentative = new \DateTimeImmutable();
            $dateSql = $dateTentative->format('Y-m-d H:i:s');

            $this->db->insert('email_information_envoi', [
                'audience' => $audience,
                'destinataire_id' => (int) $destinataire['destinataire_id'],
                'nom_destinataire' => $destinataire['nom_complet'],
                'email_destinataire' => $destinataire['email'],
                'sujet' => $sujet,
                'contenu' => $contenu,
                'statut' => 'EN_COURS',
                'erreur' => null,
                'expediteur' => self::EXPEDITEUR,
                'nom_expediteur' => $nomExpediteur,
                'date_tentative' => $dateSql,
                'date_envoi' => null,
            ]);

            $idHistorique = (int) $this->db->lastInsertId();

            try {
                $email = (new Email())
                    ->from(new Address(self::EXPEDITEUR, $nomExpediteur))
                    ->to($destinataire['email'])
                    ->subject($sujet)
                    ->text($contenu);

                $this->mailer->send($email);

                $this->db->update(
                    'email_information_envoi',
                    [
                        'statut' => 'ENVOYE',
                        'date_envoi' => (new \DateTimeImmutable())
                            ->format('Y-m-d H:i:s'),
                    ],
                    ['id' => $idHistorique]
                );

                ++$resultat['envoyes'];
            } catch (\Throwable $exception) {
                $this->db->update(
                    'email_information_envoi',
                    [
                        'statut' => 'ECHEC',
                        'erreur' => mb_substr($exception->getMessage(), 0, 60000),
                    ],
                    ['id' => $idHistorique]
                );

                ++$resultat['echecs'];
            }
        }

        return $resultat;
    }

    public function historique(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT *
             FROM email_information_envoi
             ORDER BY date_tentative DESC, id DESC
             LIMIT 200'
        );
    }
}