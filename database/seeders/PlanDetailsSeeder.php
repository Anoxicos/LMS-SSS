<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlanDetailsSeeder extends Seeder
{
    /**
     * Créer les détails d'un plan.
     */
    public function run(int $planId): void
    {
        /*
        |--------------------------------------------------------------------------
        | Vérifier l'existence du plan
        |--------------------------------------------------------------------------
        */

        $planExists = DB::table('plans')
            ->where('id', $planId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $planExists) {
            throw new InvalidArgumentException(
                "Le plan avec l'identifiant {$planId} n'existe pas."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Domaines de l'examen
        |--------------------------------------------------------------------------
        */

        $domains = [
            [
                'name' => 'Fundamentals & Introduction',
                'nb_question' => 2,
            ],
            [
                'name' => 'Why EWIS Matters & Accident History',
                'nb_question' => 2,
            ],
            [
                'name' => 'EWIS Components',
                'nb_question' => 2,
            ],
            [
                'name' => 'Wiring Degradation Mechanisms',
                'nb_question' => 2,
            ],
            [
                'name' => 'Inspection Methods & Techniques',
                'nb_question' => 3,
            ],
            [
                'name' => 'Maintenance Practices, Connectors & Terminals',
                'nb_question' => 3,
            ],
            [
                'name' => 'Bonding & Grounding',
                'nb_question' => 1,
            ],
            [
                'name' => 'Routing, Separation & SWAMP',
                'nb_question' => 1,
            ],
            [
                'name' => 'Housekeeping & Contamination Control',
                'nb_question' => 1,
            ],
            [
                'name' => 'Documentation, Tools & Case Studies',
                'nb_question' => 1,
            ],
            [
                'name' => 'Human Factors',
                'nb_question' => 1,
            ],
            [
                'name' => 'Regulatory Requirements & Compliance',
                'nb_question' => 1,
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Création ou mise à jour
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($planId, $domains) {
            foreach ($domains as $domain) {
                $existingDetail = DB::table('plan_details')
                    ->where('plan_id', $planId)
                    ->where('name', $domain['name'])
                    ->whereNull('parent_id')
                    ->first();

                if ($existingDetail) {
                    DB::table('plan_details')
                        ->where('id', $existingDetail->id)
                        ->update([
                            'nb_question' => $domain['nb_question'],
                            'deleted_at' => null,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('plan_details')->insert([
                        'plan_id' => $planId,
                        'name' => $domain['name'],
                        'nb_question' => $domain['nb_question'],
                        'own_questions' => $domain['nb_question'],
                        'total_questions' => $domain['nb_question'],
                        'parent_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $totalQuestions = array_sum(
            array_column($domains, 'nb_question')
        );

        $this->command?->info(
            "Les détails du plan {$planId} ont été créés avec succès."
        );

        $this->command?->info(
            count($domains) . " domaines et {$totalQuestions} questions."
        );
    }
}