<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use Illuminate\Database\Seeder;

class AccreditationSeeder extends Seeder
{
    public function run(): void
    {
        Accreditation::updateOrCreate(
            [
                'code' => 'easa',
            ],
            [
                'name' => 'EASA',
                'organization_name' => 'SafeSky Solution',
                'authority_name' => 'European Union Aviation Safety Agency',
                'country' => 'European Union',
                'approval_reference' => null,
                'logo_path' => 'certificates/logos/sss.png',
                'certificate_template' => 'certificates.easa',
                'is_active' => true,
            ]
        );

        Accreditation::updateOrCreate(
            [
                'code' => 'dac',
            ],
            [
                'name' => 'DAC Morocco',
                'organization_name' => 'HALIAIR',
                'authority_name' => 'Direction de l’Aéronautique Civile',
                'country' => 'Morocco',
                'approval_reference' => null,
                'logo_path' => 'certificates/logos/haliair.png',
                'certificate_template' => 'certificates.dac',
                'is_active' => true,
            ]
        );
    }
}