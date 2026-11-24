<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Candidat;

class CandidatService
{
    public function estEligible(
        Candidat $c,
        int $min = 12,
        int $max = 35
    ): array {
        $age = $c->getAge();

        $nationalite = mb_strtolower(
            trim($c->getNationalite()),
            'UTF-8'
        );

        $diplome = trim((string) $c->getDiplome());

        $okNat = $nationalite === 'ivoirienne';

        $okAge = $age !== null
            && $age >= $min
            && $age <= $max;

        $okDiplome = $diplome !== ''
            && mb_strtolower($diplome, 'UTF-8') !== 'aucun'
            && !empty($c->getDiplomeFichier());

        return [
            'eligible' => $okNat && $okAge && $okDiplome,
            'age' => $age,
            'nationalite' => $okNat,
            'ageOk' => $okAge,
            'diplomeOk' => $okDiplome,
        ];
    }
}