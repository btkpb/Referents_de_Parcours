<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AffectationEntrepriseApprenti;
use App\Entity\AffectationResponsableApprenti;
use App\Entity\Candidat;
use App\Entity\EntrepriseStage;
use App\Entity\EvaluationCompetenceEntreprise;
use App\Entity\EvaluationComportementEntreprise;
use App\Entity\EvaluationEntreprise;
use App\Entity\User;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EvaluationEntrepriseController extends AbstractController
{
    #[Route(
        '/espace-maitre-stage/apprenti/{id}/evaluation/nouvelle',
        name: 'maitre_stage_evaluation_nouvelle',
        methods: ['GET', 'POST']
    )]
    public function nouvelle(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $utilisateur = $this->getUser();

        if (
            !$utilisateur instanceof User
            || !$utilisateur->isActive()
            || !in_array('ROLE_MAITRE_STAGE', $utilisateur->getRoles(), true)
        ) {
            throw $this->createAccessDeniedException(
                'Seul un Maître de stage actif peut créer une fiche de suivi.'
            );
        }

        $candidat = $entityManager->getRepository(Candidat::class)->find($id);

        if (!$candidat instanceof Candidat) {
            throw $this->createNotFoundException('Apprenti introuvable.');
        }

        // Vérification de l'affectation officielle du Maître de stage.
        $affectationResponsable = $entityManager
            ->getRepository(AffectationResponsableApprenti::class)
            ->findOneBy([
                'candidat' => $candidat,
                'responsable' => $utilisateur,
                'typeResponsabilite' =>
                    AffectationResponsableApprenti::TYPE_MAITRE_STAGE,
                'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                'dateFin' => null,
            ]);

        if (!$affectationResponsable instanceof AffectationResponsableApprenti) {
            throw $this->createAccessDeniedException(
                'Cet apprenti ne vous est pas actuellement affecté.'
            );
        }

        // Recherche de l'affectation active de l'apprenti en entreprise.
        $placements = $entityManager
            ->getRepository(AffectationEntrepriseApprenti::class)
            ->findBy(
                ['candidat' => $candidat],
                ['dateDebut' => 'DESC']
            );

        $placement = null;

        foreach ($placements as $element) {
            if ($element instanceof AffectationEntrepriseApprenti
                && $element->estActive()
            ) {
                $placement = $element;
                break;
            }
        }

        if (!$placement instanceof AffectationEntrepriseApprenti) {
            return $this->render(
                'maitre_stage/placement_inactif.html.twig',
                [
                    'candidat' => $candidat,
                ]
            );
        }

        $entreprise = $this->entrepriseDuPlacement($placement);

        // Recherche du Référent de parcours actuellement affecté.
        $affectationReferent = $entityManager
            ->getRepository(AffectationResponsableApprenti::class)
            ->findOneBy([
                'candidat' => $candidat,
                'typeResponsabilite' =>
                    AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS,
                'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                'dateFin' => null,
            ]);

        $referent = $affectationReferent instanceof AffectationResponsableApprenti
            ? $affectationReferent->getResponsable()
            : null;

        $chantier = method_exists($placement, 'getChantier')
            ? $placement->getChantier()
            : null;

        // Création d'un nouveau brouillon.
        // Reprise du dernier brouillon pour ce placement.
        $evaluation = $entityManager
            ->getRepository(EvaluationEntreprise::class)
            ->findOneBy(
                [
                    'candidat' => $candidat,
                    'affectationEntreprise' => $placement,
                    'maitreStage' => $utilisateur,
                    'statut' => EvaluationEntreprise::STATUT_BROUILLON,
                ],
                ['createdAt' => 'DESC', 'id' => 'DESC']
            );

        if (!$evaluation instanceof EvaluationEntreprise) {
            // Aucun brouillon : creation d'une nouvelle fiche.
            $evaluation = (new EvaluationEntreprise())
                ->setCandidat($candidat)
                ->setAffectationEntreprise($placement)
                ->setMaitreStage($utilisateur)
                ->setReferent($referent)
                ->setNomApprenti($this->valeur($candidat, ['getNom']))
                ->setPrenomsApprenti($this->valeur($candidat, ['getPrenoms']))
                ->setTelephoneApprenti($this->valeur($candidat, ['getTelephone']))
                ->setMetier($this->valeur($candidat, ['getMetier']))
                ->setNomReferent(
                    $referent instanceof User
                        ? trim((string) $referent->getNom() . ' ' . (string) $referent->getPrenoms())
                        : null
                )
                ->setNomEntreprise($this->valeur($entreprise, ['getRaisonSociale']))
                ->setNomResponsable($this->valeur($entreprise, ['getNomResponsable']))
                ->setNomTuteur($this->valeur($entreprise, ['getNomTuteur']))
                ->setFonctionTuteur($this->valeur($entreprise, ['getFonctionTuteur']))
                ->setTelephoneTuteur($this->valeur($entreprise, ['getTelephoneTuteur']))
                ->setIntituleProjet($this->valeur($chantier, ['getNom']));

            if (method_exists($placement, 'getDateDebut')) {
                $evaluation->setPeriodeSuiviDebut(
                    $this->dateObjet($placement->getDateDebut())
                );
            }

            if (method_exists($placement, 'getDateFin')) {
                $evaluation->setPeriodeSuiviFin(
                    $this->dateObjet($placement->getDateFin())
                );
            }
        }

        // Ajouter uniquement les criteres comportementaux manquants.
        $criteresExistants = [];

        foreach ($evaluation->getEvaluationsComportementales() as $item) {
            $criteresExistants[$item->getCritere()] = true;
        }

        foreach (EvaluationComportementEntreprise::CRITERES as $code => $libelle) {
            if (!isset($criteresExistants[$code])) {
                $critere = (new EvaluationComportementEntreprise())
                    ->setCritere($code);

                $evaluation->addEvaluationComportementale($critere);
            }
        }

        // Reconstituer les donnees deja enregistrees.
        $donnees = $this->donneesDepuisEvaluation($evaluation);

        $erreur = null;

        if ($request->isMethod('POST')) {
            $post = $request->request->all();
            $donnees = isset($post['fiche']) && is_array($post['fiche'])
                ? $post['fiche']
                : [];

            if (!$this->isCsrfTokenValid(
                'fiche_entreprise_' . $id,
                (string) ($post['_token'] ?? '')
            )) {
                $erreur = 'Le formulaire a expiré. Veuillez le recharger.';
            } else {
                try {
                    $evaluation->setIntituleProjet(
                        $this->champ($donnees, 'intituleProjet',
                            $evaluation->getIntituleProjet())
                    );

                    $evaluation->setNomReferent(
                        $this->champ($donnees, 'nomReferent',
                            $evaluation->getNomReferent())
                    );

                    $evaluation->setNomCentre(
                        $this->champ($donnees, 'nomCentre', null)
                    );

                    $evaluation->setNomFormateur(
                        $this->champ($donnees, 'nomFormateur', null)
                    );

                    $evaluation->setTelephoneFormateur(
                        $this->champ($donnees, 'telephoneFormateur', null)
                    );

                    $evaluation->setNomEntreprise(
                        $this->champ($donnees, 'nomEntreprise',
                            $evaluation->getNomEntreprise())
                    );

                    $evaluation->setNomResponsable(
                        $this->champ($donnees, 'nomResponsable',
                            $evaluation->getNomResponsable())
                    );

                    $evaluation->setNomTuteur(
                        $this->champ($donnees, 'nomTuteur',
                            $evaluation->getNomTuteur())
                    );

                    $evaluation->setFonctionTuteur(
                        $this->champ($donnees, 'fonctionTuteur',
                            $evaluation->getFonctionTuteur())
                    );

                    $evaluation->setTelephoneTuteur(
                        $this->champ($donnees, 'telephoneTuteur',
                            $evaluation->getTelephoneTuteur())
                    );

                    $evaluation->setLieuSignature(
                        $this->champ($donnees, 'lieuSignature', null)
                    );

                    $evaluation->setDateSuivi(
                        $this->dateFormulaire(
                            $donnees['dateSuivi'] ?? null
                        ) ?? new DateTimeImmutable()
                    );

                    $evaluation->setPeriodeSuiviDebut(
                        $this->dateFormulaire(
                            $donnees['periodeSuiviDebut'] ?? null,
                            $evaluation->getPeriodeSuiviDebut()
                        )
                    );

                    $evaluation->setPeriodeSuiviFin(
                        $this->dateFormulaire(
                            $donnees['periodeSuiviFin'] ?? null,
                            $evaluation->getPeriodeSuiviFin()
                        )
                    );

                    $debut = $evaluation->getPeriodeSuiviDebut();
                    $fin = $evaluation->getPeriodeSuiviFin();

                    if ($debut !== null && $fin !== null && $debut > $fin) {
                        throw new \InvalidArgumentException(
                            'La fin de période doit être postérieure au début.'
                        );
                    }

                    $evaluation->setPointsForts(
                        $this->champ($donnees, 'pointsForts', null)
                    );

                    $evaluation->setPointsAAmeliorer(
                        $this->champ($donnees, 'pointsAAmeliorer', null)
                    );

                    $bilan = $this->champ($donnees, 'bilanGeneral', null);
                    $evaluation->setBilanGeneral($bilan);

                    $evaluation->setAvisMaitreStage(
                        $this->champ($donnees, 'avisMaitreStage', null)
                    );

                    // Enregistrement des appréciations comportementales.
                    $comportements = isset($donnees['comportement'])
                        && is_array($donnees['comportement'])
                            ? $donnees['comportement']
                            : [];

                    foreach ($evaluation->getEvaluationsComportementales() as $item) {
                        $code = $item->getCritere();
                        $appreciation = $comportements[$code] ?? null;

                        if ($appreciation === '') {
                            $appreciation = null;
                        }

                        $item->setAppreciation(
                            $appreciation === null
                                ? null
                                : (string) $appreciation
                        );
                    }

                    // Les intitulés techniques sont saisis depuis le vrai RF.
                    $competences = isset($donnees['competences'])
                        && is_array($donnees['competences'])
                            ? $donnees['competences']
                            : [];

                    $competencesExistantes = [];

                    foreach ($evaluation->getCompetencesTechniques() as $existante) {
                        $competencesExistantes[$existante->getOrdre()] = $existante;
                    }

                    foreach (array_values($competences) as $index => $ligne) {
                        if (!is_array($ligne)) {
                            continue;
                        }

                        $intitule = trim((string) ($ligne['intitule'] ?? ''));

                        // Une ligne vide ne supprime aucune donnee.
                        if ($intitule === '') {
                            continue;
                        }

                        $ordre = $index + 1;
                        $competence = $competencesExistantes[$ordre] ?? null;

                        if (!$competence instanceof EvaluationCompetenceEntreprise) {
                            $competence = new EvaluationCompetenceEntreprise();
                            $competence->setOrdre($ordre);
                            $evaluation->addCompetenceTechnique($competence);
                        }

                        $niveau = trim((string) ($ligne['niveau'] ?? ''));
                        $reference = trim((string) ($ligne['referenceRf'] ?? ''));
                        $observation = trim((string) ($ligne['observations'] ?? ''));

                        $competence
                            ->setIntituleCompetence($intitule)
                            ->setReferenceRf($reference !== '' ? $reference : null)
                            ->setNiveau($niveau !== '' ? $niveau : null)
                            ->setObservations($observation !== '' ? $observation : null);
                    }
                    $evaluation->setStatut(
                        EvaluationEntreprise::STATUT_BROUILLON
                    );

                    $entityManager->persist($evaluation);
                    $entityManager->flush();

                    $this->addFlash(
                        'success',
                        'La fiche de suivi a été enregistrée en brouillon.'
                    );

                    return $this->redirectToRoute('maitre_stage_index');

                } catch (\InvalidArgumentException $exception) {
                    $erreur = $exception->getMessage();
                }
            }
        }

        if ($erreur !== null) {
            $this->addFlash('danger', $erreur);
        }

        return $this->render('maitre_stage/evaluation_form.html.twig', [
            'candidat' => $candidat,
            'placement' => $placement,
            'evaluation' => $evaluation,
            'donnees' => $donnees,
            'criteres' => EvaluationComportementEntreprise::CRITERES,
            'appreciations' => EvaluationComportementEntreprise::APPRECIATIONS,
            'niveauxTechniques' => EvaluationCompetenceEntreprise::NIVEAUX,
            'bilans' => EvaluationEntreprise::BILANS,
        ]);
    }

    private function donneesDepuisEvaluation(EvaluationEntreprise $evaluation): array
    {
        $donnees = [
            'intituleProjet' => $evaluation->getIntituleProjet() ?? '',
            'nomReferent' => $evaluation->getNomReferent() ?? '',
            'periodeSuiviDebut' => $evaluation->getPeriodeSuiviDebut()?->format('Y-m-d') ?? '',
            'periodeSuiviFin' => $evaluation->getPeriodeSuiviFin()?->format('Y-m-d') ?? '',
            'dateSuivi' => $evaluation->getDateSuivi()?->format('Y-m-d') ?? '',
            'nomCentre' => $evaluation->getNomCentre() ?? '',
            'nomFormateur' => $evaluation->getNomFormateur() ?? '',
            'telephoneFormateur' => $evaluation->getTelephoneFormateur() ?? '',
            'nomEntreprise' => $evaluation->getNomEntreprise() ?? '',
            'nomResponsable' => $evaluation->getNomResponsable() ?? '',
            'nomTuteur' => $evaluation->getNomTuteur() ?? '',
            'fonctionTuteur' => $evaluation->getFonctionTuteur() ?? '',
            'telephoneTuteur' => $evaluation->getTelephoneTuteur() ?? '',
            'pointsForts' => $evaluation->getPointsForts() ?? '',
            'pointsAAmeliorer' => $evaluation->getPointsAAmeliorer() ?? '',
            'bilanGeneral' => $evaluation->getBilanGeneral() ?? '',
            'avisMaitreStage' => $evaluation->getAvisMaitreStage() ?? '',
            'lieuSignature' => $evaluation->getLieuSignature() ?? '',
            'competences' => [],
            'comportement' => [],
        ];

        $competences = [];

        foreach ($evaluation->getCompetencesTechniques() as $competence) {
            $competences[] = $competence;
        }

        usort(
            $competences,
            static fn (EvaluationCompetenceEntreprise $a, EvaluationCompetenceEntreprise $b): int
                => $a->getOrdre() <=> $b->getOrdre()
        );

        foreach ($competences as $competence) {
            $donnees['competences'][] = [
                'referenceRf' => $competence->getReferenceRf() ?? '',
                'intitule' => $competence->getIntituleCompetence(),
                'niveau' => $competence->getNiveau() ?? '',
                'observations' => $competence->getObservations() ?? '',
            ];
        }

        foreach ($evaluation->getEvaluationsComportementales() as $item) {
            $donnees['comportement'][$item->getCritere()] =
                $item->getAppreciation() ?? '';
        }

        while (count($donnees['competences']) < 5) {
            $donnees['competences'][] = [
                'referenceRf' => '',
                'intitule' => '',
                'niveau' => '',
                'observations' => '',
            ];
        }

        return $donnees;
    }
    private function entrepriseDuPlacement(
        AffectationEntrepriseApprenti $placement
    ): ?EntrepriseStage {
        foreach (['getEntreprise', 'getEntrepriseStage'] as $methode) {
            if (method_exists($placement, $methode)) {
                $entreprise = $placement->$methode();

                return $entreprise instanceof EntrepriseStage
                    ? $entreprise
                    : null;
            }
        }

        return null;
    }

    private function valeur(?object $objet, array $methodes): ?string
    {
        if ($objet === null) {
            return null;
        }

        foreach ($methodes as $methode) {
            if (method_exists($objet, $methode)) {
                $valeur = $objet->$methode();

                if ($valeur !== null && trim((string) $valeur) !== '') {
                    return trim((string) $valeur);
                }
            }
        }

        return null;
    }

    private function champ(
        array $donnees,
        string $cle,
        ?string $valeurParDefaut
    ): ?string {
        if (!array_key_exists($cle, $donnees)) {
            return $valeurParDefaut;
        }

        $valeur = trim((string) $donnees[$cle]);

        return $valeur === '' ? null : $valeur;
    }

    private function dateObjet(mixed $valeur): ?DateTimeImmutable
    {
        if ($valeur instanceof DateTimeImmutable) {
            return $valeur;
        }

        if ($valeur instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($valeur);
        }

        return null;
    }

    private function dateFormulaire(
        mixed $valeur,
        ?DateTimeImmutable $valeurParDefaut = null
    ): ?DateTimeImmutable {
        if ($valeur === null || trim((string) $valeur) === '') {
            return $valeurParDefaut;
        }

        try {
            return new DateTimeImmutable((string) $valeur);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Une date saisie est invalide.');
        }
    }
}