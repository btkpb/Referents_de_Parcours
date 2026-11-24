<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Admission;
use App\Entity\AffectationResponsableApprenti;
use App\Entity\Candidat;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AffectationResponsableApprentiService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Affecte un responsable à un apprenti.
     *
     * Une affectation active précédente du même type est clôturée.
     * Son historique est conservé.
     */
    public function affecter(
        Candidat $candidat,
        User $responsable,
        string $typeResponsabilite,
        User $affectePar
    ): AffectationResponsableApprenti {
        $typesAutorises = [
            AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS,
            AffectationResponsableApprenti::TYPE_FORMATEUR,
            AffectationResponsableApprenti::TYPE_MAITRE_STAGE,
        ];

        if (!in_array($typeResponsabilite, $typesAutorises, true)) {
            throw new InvalidArgumentException(
                'Type de responsabilite non autorise.'
            );
        }

        $candidatId = $candidat->getId();
        $responsableId = $responsable->getId();
        $affecteParId = $affectePar->getId();

        if (
            $candidatId === null
            || $responsableId === null
            || $affecteParId === null
        ) {
            throw new InvalidArgumentException(
                'Le candidat, le responsable et l auteur doivent etre enregistres.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function (EntityManagerInterface $em) use (
                $candidatId,
                $responsableId,
                $affecteParId,
                $typeResponsabilite
            ): AffectationResponsableApprenti {
                $candidatVerifie = $em->find(Candidat::class, $candidatId);
                $responsableVerifie = $em->find(User::class, $responsableId);

                $affecteurVerifie = $em->find(User::class, $affecteParId);

                if (!$candidatVerifie instanceof Candidat) {
                    throw new DomainException('Candidat introuvable.');
                }

                if (!$responsableVerifie instanceof User) {
                    throw new DomainException('Responsable introuvable.');
                }

                if (!$affecteurVerifie instanceof User) {
                    throw new DomainException(
                        'Auteur de l affectation introuvable.'
                    );
                }

                if (!$affecteurVerifie->isActive()) {
                    throw new AccessDeniedException(
                        'Un compte desactive ne peut pas effectuer une affectation.'
                    );
                }

                // ROLE_ADMIN est conserve comme ancien profil Gestionnaire.
                $rolesAuteurAutorises = [
                    'ROLE_GESTIONNAIRE',
                    'ROLE_ADMIN',
                    'ROLE_ADMINISTRATEUR',
                    'ROLE_SUPER_ADMIN',
                ];

                if (count(array_intersect(
                    $rolesAuteurAutorises,
                    $affecteurVerifie->getRoles()
                )) === 0) {
                    throw new AccessDeniedException(
                        'Vous ne disposez pas des droits necessaires pour gerer les affectations.'
                    );
                }

                // Verrouille le dossier du candidat pendant toute l operation.
                $em->lock(
                    $candidatVerifie,
                    LockMode::PESSIMISTIC_WRITE
                );

                // Seuls les candidats effectivement admis sont affectables.
                $admission = $em->getRepository(Admission::class)
                    ->findOneBy(['candidat' => $candidatVerifie]);

                if (!$admission instanceof Admission
                    || $admission->getDecision() !== 'ADMIS'
                ) {
                    throw new DomainException(
                        'Impossible d affecter un responsable : candidat non admis.'
                    );
                }

                // Le responsable doit disposer d un compte actif.
                if (!$responsableVerifie->isActive()) {
                    throw new DomainException(
                        'Le compte du responsable est desactive.'
                    );
                }

                // Verifie le role professionnel correspondant.
                $controleRole = (new AffectationResponsableApprenti())
                    ->setTypeResponsabilite($typeResponsabilite)
                    ->setResponsable($responsableVerifie);

                if (!$controleRole->responsablePossedeRoleAttendu()) {
                    throw new DomainException(
                        'Le responsable ne possede pas le role requis.'
                    );
                }

                // Recherche toutes les affectations actives de ce type.
                $affectationsActives = $em
                    ->getRepository(AffectationResponsableApprenti::class)
                    ->findBy([
                        'candidat' => $candidatVerifie,
                        'typeResponsabilite' => $typeResponsabilite,
                        'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                        'dateFin' => null,
                    ]);

                if (count($affectationsActives) > 1) {
                    throw new LogicException(
                        'Plusieurs affectations actives existent deja pour cette responsabilite. '
                        . 'Une verification manuelle est necessaire.'
                    );
                }

                $ancienneAffectation = $affectationsActives[0] ?? null;

                // Une nouvelle demande identique ne cree pas de doublon.
                if ($ancienneAffectation instanceof AffectationResponsableApprenti
                    && $ancienneAffectation->getResponsable()?->getId()
                        === $responsableVerifie->getId()
                ) {
                    return $ancienneAffectation;
                }

                $aujourdhui = new \DateTime('today');

                // Cloture de l ancienne affectation sans supprimer son historique.
                if ($ancienneAffectation instanceof AffectationResponsableApprenti) {
                    $ancienneAffectation
                        ->setDateFin($aujourdhui)
                        ->setStatut(
                            AffectationResponsableApprenti::STATUT_TERMINEE
                        )
                        ->setMotifFin('Remplacement du responsable.');
                }

                // Creation de la nouvelle affectation.
                $nouvelleAffectation = (new AffectationResponsableApprenti())
                    ->setCandidat($candidatVerifie)
                    ->setResponsable($responsableVerifie)
                    ->setAffectePar($affecteurVerifie)
                    ->setTypeResponsabilite($typeResponsabilite)
                    ->setDateDebut($aujourdhui);

                $em->persist($nouvelleAffectation);

                return $nouvelleAffectation;
            }
        );
    }
}