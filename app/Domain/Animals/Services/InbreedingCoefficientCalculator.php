<?php

namespace App\Domain\Animals\Services;

use App\Domain\Animals\Models\Animal;

class InbreedingCoefficientCalculator
{
    /**
     * Build the multi-generational pedigree tree up to the specified max depth.
     *
     * @return array<string, mixed>
     */
    public function getPedigreeTree(Animal $animal, int $maxDepth = 3, int $currentDepth = 1): array
    {
        $node = [
            'id' => $animal->id,
            'tag_number' => $animal->tag_number,
            'name' => $animal->name,
            'sex' => $animal->sex,
            'breed' => $animal->breed?->name,
            'depth' => $currentDepth,
            'sire' => null,
            'dam' => null,
        ];

        if ($currentDepth < $maxDepth) {
            if ($animal->sire) {
                $node['sire'] = $this->getPedigreeTree($animal->sire, $maxDepth, $currentDepth + 1);
            }
            if ($animal->dam) {
                $node['dam'] = $this->getPedigreeTree($animal->dam, $maxDepth, $currentDepth + 1);
            }
        }

        return $node;
    }

    /**
     * Compute Wright's coefficient of inbreeding (F_X) between sire and dam.
     * F_X = sum((1/2)^(n1 + n2 + 1) * (1 + F_A))
     */
    public function calculateInbreedingCoefficient(?Animal $sire, ?Animal $dam, int $maxGenerations = 4): float
    {
        if (! $sire || ! $dam) {
            return 0.0000;
        }

        // Direct parent-offspring mating or full siblings
        if ($sire->id === $dam->id) {
            return 1.0000;
        }

        $sireAncestors = $this->getAncestorsWithGenerations($sire, $maxGenerations);
        $damAncestors = $this->getAncestorsWithGenerations($dam, $maxGenerations);

        $commonAncestors = array_intersect(array_keys($sireAncestors), array_keys($damAncestors));

        if (empty($commonAncestors)) {
            return 0.0000;
        }

        $totalFx = 0.0;

        foreach ($commonAncestors as $ancestorId) {
            $n1 = $sireAncestors[$ancestorId];
            $n2 = $damAncestors[$ancestorId];

            // Formula: (0.5)^(n1 + n2 + 1)
            $pathFx = pow(0.5, $n1 + $n2 + 1);
            $totalFx += $pathFx;
        }

        return round(min($totalFx, 1.0), 4);
    }

    /**
     * Find ancestors mapping: [animal_id => generation_distance]
     *
     * @return array<int, int>
     */
    private function getAncestorsWithGenerations(Animal $animal, int $maxGenerations, int $currentDistance = 0): array
    {
        $ancestors = [];

        if ($currentDistance >= $maxGenerations) {
            return $ancestors;
        }

        if ($animal->sire_id) {
            $ancestors[$animal->sire_id] = $currentDistance + 1;
            if ($animal->sire) {
                $sireAncestors = $this->getAncestorsWithGenerations($animal->sire, $maxGenerations, $currentDistance + 1);
                foreach ($sireAncestors as $id => $dist) {
                    if (! isset($ancestors[$id]) || $dist < $ancestors[$id]) {
                        $ancestors[$id] = $dist;
                    }
                }
            }
        }

        if ($animal->dam_id) {
            $ancestors[$animal->dam_id] = $currentDistance + 1;
            if ($animal->dam) {
                $damAncestors = $this->getAncestorsWithGenerations($animal->dam, $maxGenerations, $currentDistance + 1);
                foreach ($damAncestors as $id => $dist) {
                    if (! isset($ancestors[$id]) || $dist < $ancestors[$id]) {
                        $ancestors[$id] = $dist;
                    }
                }
            }
        }

        return $ancestors;
    }
}
