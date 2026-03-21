<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

/**
 * Canonical dispensing / packaging units aligned with common pharmacy practice:
 * SI units (ISO 80000), EDQM-style dose-form language where helpful, and typical retail SKUs.
 * Stored `name` values match what we persist on products.unit_of_measure (string).
 */
class PharmaceuticalUnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Count / discrete (ISO “one” / each)
            ['code' => 'EA', 'name' => 'Each', 'category' => 'count', 'sort_order' => 1],
            ['code' => 'UNIT', 'name' => 'Unit', 'category' => 'count', 'sort_order' => 2],
            ['code' => 'DOSE', 'name' => 'Dose', 'category' => 'count', 'sort_order' => 3],

            // Solid oral (common EDQM / SmPC wording, shortened for dropdowns)
            ['code' => 'TAB', 'name' => 'Tablet', 'category' => 'solid_oral', 'sort_order' => 20],
            ['code' => 'TAB_FC', 'name' => 'Film-coated tablet', 'category' => 'solid_oral', 'sort_order' => 21],
            ['code' => 'CAP', 'name' => 'Capsule', 'category' => 'solid_oral', 'sort_order' => 22],
            ['code' => 'CAP_SOFT', 'name' => 'Soft capsule', 'category' => 'solid_oral', 'sort_order' => 23],
            ['code' => 'LOZ', 'name' => 'Lozenge', 'category' => 'solid_oral', 'sort_order' => 24],
            ['code' => 'GRAN', 'name' => 'Granules for oral suspension', 'category' => 'solid_oral', 'sort_order' => 25],
            ['code' => 'ODT', 'name' => 'Orodispersible tablet', 'category' => 'solid_oral', 'sort_order' => 26],

            // Powders / sachets
            ['code' => 'SACH', 'name' => 'Sachet', 'category' => 'powder_oral', 'sort_order' => 40],
            ['code' => 'PWD', 'name' => 'Powder', 'category' => 'powder_oral', 'sort_order' => 41],

            // Liquids (volumes often also captured in product “volume” field)
            ['code' => 'ML', 'name' => 'mL', 'category' => 'metric_volume', 'sort_order' => 50],
            ['code' => 'L', 'name' => 'L', 'category' => 'metric_volume', 'sort_order' => 51],
            ['code' => 'ORAL_SOL', 'name' => 'Oral solution', 'category' => 'liquid_oral', 'sort_order' => 52],
            ['code' => 'ORAL_SUSP', 'name' => 'Oral suspension', 'category' => 'liquid_oral', 'sort_order' => 53],
            ['code' => 'SYRUP', 'name' => 'Syrup', 'category' => 'liquid_oral', 'sort_order' => 54],
            ['code' => 'ELIX', 'name' => 'Elixir', 'category' => 'liquid_oral', 'sort_order' => 55],

            // Sterile / parenteral
            ['code' => 'AMP', 'name' => 'Ampoule', 'category' => 'sterile', 'sort_order' => 70],
            ['code' => 'VIAL', 'name' => 'Vial', 'category' => 'sterile', 'sort_order' => 71],
            ['code' => 'PFS', 'name' => 'Pre-filled syringe', 'category' => 'sterile', 'sort_order' => 72],
            ['code' => 'IV_BAG', 'name' => 'IV bag', 'category' => 'sterile', 'sort_order' => 73],

            // Ophthalmic / otic / nasal
            ['code' => 'EYE_DROP', 'name' => 'Eye Drop', 'category' => 'ophthalmic', 'sort_order' => 80],
            ['code' => 'EAR_DROP', 'name' => 'Ear Drop', 'category' => 'otic', 'sort_order' => 81],
            ['code' => 'NAS_SPR', 'name' => 'Nasal spray', 'category' => 'nasal', 'sort_order' => 82],
            ['code' => 'DROP', 'name' => 'Drop', 'category' => 'liquid', 'sort_order' => 83],

            // Respiratory
            ['code' => 'MDI', 'name' => 'Metered-dose inhaler', 'category' => 'respiratory', 'sort_order' => 90],
            ['code' => 'DPI', 'name' => 'Dry powder inhaler', 'category' => 'respiratory', 'sort_order' => 91],
            ['code' => 'NEB', 'name' => 'Nebuliser solution', 'category' => 'respiratory', 'sort_order' => 92],
            ['code' => 'PUFF', 'name' => 'Puff', 'category' => 'respiratory', 'sort_order' => 93],
            ['code' => 'CAN', 'name' => 'Canister', 'category' => 'respiratory', 'sort_order' => 94],

            // Topical / transdermal
            ['code' => 'CREAM', 'name' => 'Cream', 'category' => 'topical', 'sort_order' => 100],
            ['code' => 'OINT', 'name' => 'Ointment', 'category' => 'topical', 'sort_order' => 101],
            ['code' => 'GEL', 'name' => 'Gel', 'category' => 'topical', 'sort_order' => 102],
            ['code' => 'LOT', 'name' => 'Lotion', 'category' => 'topical', 'sort_order' => 103],
            ['code' => 'PATCH', 'name' => 'Patch', 'category' => 'topical', 'sort_order' => 104],
            ['code' => 'APP', 'name' => 'Application', 'category' => 'topical', 'sort_order' => 105],
            ['code' => 'SPRAY_TOP', 'name' => 'Spray', 'category' => 'topical', 'sort_order' => 106],

            // Rectal / vaginal
            ['code' => 'SUPP', 'name' => 'Suppository', 'category' => 'rectal', 'sort_order' => 110],
            ['code' => 'PESS', 'name' => 'Pessary', 'category' => 'vaginal', 'sort_order' => 111],

            // Mass / activity (often with strength in “volume” field)
            ['code' => 'G', 'name' => 'g', 'category' => 'metric_mass', 'sort_order' => 120],
            ['code' => 'MG', 'name' => 'mg', 'category' => 'metric_mass', 'sort_order' => 121],
            ['code' => 'MCG', 'name' => 'mcg', 'category' => 'metric_mass', 'sort_order' => 122],
            ['code' => 'IU', 'name' => 'IU', 'category' => 'activity', 'sort_order' => 123],

            // Packaging / trade units (retail)
            ['code' => 'PACK', 'name' => 'Pack', 'category' => 'packaging', 'sort_order' => 140],
            ['code' => 'BOX', 'name' => 'Box', 'category' => 'packaging', 'sort_order' => 141],
            ['code' => 'BOTTLE', 'name' => 'Bottle', 'category' => 'container', 'sort_order' => 142],
            ['code' => 'TUBE', 'name' => 'Tube', 'category' => 'container', 'sort_order' => 143],
            ['code' => 'INHALER', 'name' => 'Inhaler', 'category' => 'device', 'sort_order' => 144],
        ];

        foreach ($rows as $row) {
            UnitOfMeasure::updateOrCreate(
                ['name' => $row['name']],
                [
                    'code' => $row['code'],
                    'category' => $row['category'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
