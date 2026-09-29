<?php

namespace Database\Seeders;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\Breed;
use App\Domain\Animals\Models\Species;
use App\Domain\Animals\Models\WeightRecord;
use App\Domain\Breeding\Models\BreedingEvent;
use App\Domain\Breeding\Models\Pregnancy;
use App\Domain\Climate\Models\ClimateReading;
use App\Domain\Feed\Models\FeedConsumption;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Finance\Models\FinancialTransaction;
use App\Domain\Finance\Models\Supplier;
use App\Domain\Health\Models\HealthCase;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Health\Models\Vaccination;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Milk\Models\MilkSession;
use App\Domain\Organization\Models\Barn;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\Pen;
use App\Domain\Workforce\Models\FarmTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PilotFarmSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $owner = User::firstOrCreate(
            ['email' => 'owner@farm.local'],
            [
                'name' => 'Tariq Mehmood (Farm Owner)',
                'password' => Hash::make('password'),
            ]
        );

        $vet = User::firstOrCreate(
            ['email' => 'vet@farm.local'],
            [
                'name' => 'Dr. Asim Raza (Veterinarian)',
                'password' => Hash::make('password'),
            ]
        );

        $worker = User::firstOrCreate(
            ['email' => 'worker@farm.local'],
            [
                'name' => 'Muhammad Bilal (Herd Manager)',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Organization & Farm
        $org = Organization::create([
            'name' => 'GreenPastures Agro & Dairy',
            'slug' => 'greenpastures-agro',
            'contact_email' => 'contact@greenpastures.farm',
            'contact_phone' => '+92-300-8451234',
            'country' => 'PAK',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'settings' => [
                'milk_price_per_liter' => 195.00,
                'goat_milk_price_per_liter' => 280.00,
                'compliance_pack' => 'PK-LIVESTOCK-V1',
            ],
        ]);

        $farm = Farm::create([
            'organization_id' => $org->id,
            'name' => 'Al-Falah Pilot Dairy & Goat Farm',
            'code' => 'ALF-FARM-01',
            'type' => 'dairy_mixed',
            'location' => 'Kasur Road, Punjab, Pakistan',
            'latitude' => 31.1172,
            'longitude' => 74.4503,
            'total_area' => 20.00,
            'area_unit' => 'acres',
            'timezone' => 'Asia/Karachi',
            'climate_settings' => [
                'cooling_fans_threshold_c' => 28.0,
                'misting_thi_threshold' => 76.0,
            ],
        ]);

        // Barns & Pens
        $cattleBarn = Barn::create([
            'farm_id' => $farm->id,
            'name' => 'Main Cattle Free-Stall Barn',
            'code' => 'BARN-CATTLE-01',
            'type' => 'milking',
            'capacity' => 15,
        ]);

        $goatBarn = Barn::create([
            'farm_id' => $farm->id,
            'name' => 'Elevated Goat Shed',
            'code' => 'SHED-GOAT-01',
            'type' => 'milking',
            'capacity' => 30,
        ]);

        $penCowMilking = Pen::create([
            'barn_id' => $cattleBarn->id,
            'name' => 'Milking Cows Pen A',
            'code' => 'PEN-C-01',
            'capacity' => 8,
        ]);

        $penCowDry = Pen::create([
            'barn_id' => $cattleBarn->id,
            'name' => 'Dry & Maternity Pen',
            'code' => 'PEN-C-02',
            'capacity' => 4,
        ]);

        $penGoatMilking = Pen::create([
            'barn_id' => $goatBarn->id,
            'name' => 'Milking Does Pen G1',
            'code' => 'PEN-G-01',
            'capacity' => 15,
        ]);

        $penGoatDry = Pen::create([
            'barn_id' => $goatBarn->id,
            'name' => 'Dry Does & Kids Pen G2',
            'code' => 'PEN-G-02',
            'capacity' => 15,
        ]);

        // 3. Species & Breeds
        $cattleSpecies = Species::create([
            'code' => 'cattle',
            'name' => 'Cattle',
            'scientific_name' => 'Bos taurus / Bos indicus',
            'default_gestation_days' => 283,
            'default_lactation_days' => 305,
            'typical_birth_weight_kg' => 32.0,
            'typical_adult_weight_kg' => 480.0,
            'is_milk_producing' => true,
            'is_meat_producing' => true,
        ]);

        $goatSpecies = Species::create([
            'code' => 'goat',
            'name' => 'Goat',
            'scientific_name' => 'Capra hircus',
            'default_gestation_days' => 150,
            'default_lactation_days' => 210,
            'typical_birth_weight_kg' => 3.5,
            'typical_adult_weight_kg' => 55.0,
            'is_milk_producing' => true,
            'is_meat_producing' => true,
        ]);

        $buffaloSpecies = Species::create([
            'code' => 'buffalo',
            'name' => 'Water Buffalo',
            'scientific_name' => 'Bubalus bubalis',
            'default_gestation_days' => 310,
            'default_lactation_days' => 300,
            'typical_birth_weight_kg' => 38.0,
            'typical_adult_weight_kg' => 550.0,
            'is_milk_producing' => true,
            'is_meat_producing' => true,
        ]);

        $camelSpecies = Species::create([
            'code' => 'camel',
            'name' => 'Camel',
            'scientific_name' => 'Camelus dromedarius',
            'default_gestation_days' => 380,
            'default_lactation_days' => 365,
            'typical_birth_weight_kg' => 35.0,
            'typical_adult_weight_kg' => 520.0,
            'is_milk_producing' => true,
            'is_meat_producing' => true,
        ]);

        $breedSahiwal = Breed::create([
            'species_id' => $cattleSpecies->id,
            'code' => 'SAH',
            'name' => 'Sahiwal',
            'origin_country' => 'Pakistan',
            'standard_mature_weight_kg' => 450.0,
            'target_daily_yield_liters' => 16.0,
        ]);

        $breedHF = Breed::create([
            'species_id' => $cattleSpecies->id,
            'code' => 'HF',
            'name' => 'Holstein Friesian Cross',
            'origin_country' => 'Netherlands/Cross',
            'standard_mature_weight_kg' => 550.0,
            'target_daily_yield_liters' => 24.0,
        ]);

        $breedJersey = Breed::create([
            'species_id' => $cattleSpecies->id,
            'code' => 'JER',
            'name' => 'Jersey Cross',
            'origin_country' => 'Jersey/Cross',
            'standard_mature_weight_kg' => 420.0,
            'target_daily_yield_liters' => 18.0,
        ]);

        $breedBeetal = Breed::create([
            'species_id' => $goatSpecies->id,
            'code' => 'BTL',
            'name' => 'Beetal',
            'origin_country' => 'Pakistan (Punjab)',
            'standard_mature_weight_kg' => 55.0,
            'target_daily_yield_liters' => 3.2,
        ]);

        $breedKamori = Breed::create([
            'species_id' => $goatSpecies->id,
            'code' => 'KMR',
            'name' => 'Kamori',
            'origin_country' => 'Pakistan (Sindh)',
            'standard_mature_weight_kg' => 50.0,
            'target_daily_yield_liters' => 3.0,
        ]);

        $breedSaanen = Breed::create([
            'species_id' => $goatSpecies->id,
            'code' => 'SAA',
            'name' => 'Saanen Cross',
            'origin_country' => 'Switzerland/Cross',
            'standard_mature_weight_kg' => 60.0,
            'target_daily_yield_liters' => 3.8,
        ]);

        // 4. Feeds Catalog
        $silage = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Corn Silage (Pioneer)',
            'code' => 'SIL-01',
            'category' => 'silage',
            'unit' => 'kg',
            'current_stock' => 4200.0,
            'minimum_stock_alert' => 1000.0,
            'cost_per_unit' => 16.50,
            'dry_matter_percentage' => 33.5,
            'crude_protein_percentage' => 8.5,
        ]);

        $concentrate = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Dairy Wanta 18% CP High Milk',
            'code' => 'WNT-18',
            'category' => 'concentrate',
            'unit' => 'kg',
            'current_stock' => 1100.0,
            'minimum_stock_alert' => 300.0,
            'cost_per_unit' => 88.00,
            'dry_matter_percentage' => 89.0,
            'crude_protein_percentage' => 18.2,
        ]);

        $lucerneHay = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Alfalfa / Lucerne Sun-Cured Hay',
            'code' => 'LUC-HAY',
            'category' => 'hay_dry',
            'unit' => 'kg',
            'current_stock' => 850.0,
            'minimum_stock_alert' => 200.0,
            'cost_per_unit' => 45.00,
            'dry_matter_percentage' => 88.0,
            'crude_protein_percentage' => 19.5,
        ]);

        $goatFeed = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Goat High-Protein Ration Pellets',
            'code' => 'GT-PEL',
            'category' => 'concentrate',
            'unit' => 'kg',
            'current_stock' => 420.0,
            'minimum_stock_alert' => 150.0,
            'cost_per_unit' => 75.00,
            'dry_matter_percentage' => 90.0,
            'crude_protein_percentage' => 16.0,
        ]);

        $minerals = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Chelated Mineral Mixture & Yeast',
            'code' => 'MIN-MIX',
            'category' => 'mineral_premix',
            'unit' => 'kg',
            'current_stock' => 95.0,
            'minimum_stock_alert' => 25.0,
            'cost_per_unit' => 220.00,
            'dry_matter_percentage' => 96.0,
        ]);

        // 5. Medicines Catalog
        $amox = Medicine::create([
            'organization_id' => $org->id,
            'name' => 'Amoxicillin Trihydrate 15%',
            'active_ingredient' => 'Amoxicillin',
            'category' => 'antibiotic',
            'is_antimicrobial' => true,
            'default_dosage' => '15ml per 100kg body weight',
            'dosage_unit' => 'ml',
            'route_of_administration' => 'Intramuscular',
            'milk_withdrawal_days' => 3,
            'meat_withdrawal_days' => 14,
            'unit_cost' => 1250.00,
            'current_stock' => 8.0,
            'stock_unit' => 'vial_100ml',
        ]);

        $meloxicam = Medicine::create([
            'organization_id' => $org->id,
            'name' => 'Meloxicam 20mg/ml Anti-inflammatory',
            'active_ingredient' => 'Meloxicam',
            'category' => 'anti_inflammatory',
            'is_antimicrobial' => false,
            'default_dosage' => '10ml per cow',
            'dosage_unit' => 'ml',
            'route_of_administration' => 'Subcutaneous',
            'milk_withdrawal_days' => 5,
            'meat_withdrawal_days' => 15,
            'unit_cost' => 850.00,
            'current_stock' => 6.0,
            'stock_unit' => 'vial_50ml',
        ]);

        $fmdVaccine = Medicine::create([
            'organization_id' => $org->id,
            'name' => 'Aftovaxpur FMD Oil Adjuvant Vaccine',
            'active_ingredient' => 'Inactivated FMD Virus (O, A, Asia-1)',
            'category' => 'vaccine',
            'is_antimicrobial' => false,
            'default_dosage' => '2ml',
            'dosage_unit' => 'ml',
            'route_of_administration' => 'Subcutaneous',
            'milk_withdrawal_days' => 0,
            'meat_withdrawal_days' => 0,
            'unit_cost' => 450.00,
            'current_stock' => 25.0,
            'stock_unit' => 'dose',
        ]);

        // 6. Pilot Animals: 5 Cows + 10 Goats
        // 5 Cows:
        $cowsData = [
            [
                'tag' => 'PK-COW-001',
                'name' => 'Gulabo (Pure Sahiwal)',
                'breed_id' => $breedSahiwal->id,
                'pen_id' => $penCowMilking->id,
                'status' => 'lactating',
                'parity' => 2,
                'weight' => 435.0,
                'birth_date' => Carbon::now()->subMonths(44),
                'acq_date' => Carbon::now()->subMonths(20),
                'eid' => '982000001001',
                'qr' => 'FMS-COW-001-QR',
                'notes' => 'High butterfat yield, placid temperament, calm machine milker.',
            ],
            [
                'tag' => 'PK-COW-002',
                'name' => 'Rani (Sahiwal F1 Cross)',
                'breed_id' => $breedSahiwal->id,
                'pen_id' => $penCowMilking->id,
                'status' => 'lactating',
                'parity' => 3,
                'weight' => 460.0,
                'birth_date' => Carbon::now()->subMonths(52),
                'acq_date' => Carbon::now()->subMonths(24),
                'eid' => '982000001002',
                'qr' => 'FMS-COW-002-QR',
                'notes' => 'Top producing indigenous cross, disease resistant.',
            ],
            [
                'tag' => 'PK-COW-003',
                'name' => 'Chameli (Holstein Cross)',
                'breed_id' => $breedHF->id,
                'pen_id' => $penCowMilking->id,
                'status' => 'lactating',
                'parity' => 1,
                'weight' => 510.0,
                'birth_date' => Carbon::now()->subMonths(30),
                'acq_date' => Carbon::now()->subMonths(12),
                'eid' => '982000001003',
                'qr' => 'FMS-COW-003-QR',
                'notes' => 'First calver, high peak volume (22L/day), requires extra cooling.',
            ],
            [
                'tag' => 'PK-COW-004',
                'name' => 'Malka (Sahiwal Proven)',
                'breed_id' => $breedSahiwal->id,
                'pen_id' => $penCowDry->id,
                'status' => 'dry',
                'parity' => 4,
                'weight' => 475.0,
                'birth_date' => Carbon::now()->subMonths(66),
                'acq_date' => Carbon::now()->subMonths(36),
                'eid' => '982000001004',
                'qr' => 'FMS-COW-004-QR',
                'notes' => 'Dry-off period, advanced pregnancy, expected calving in 22 days.',
            ],
            [
                'tag' => 'PK-COW-005',
                'name' => 'Sundri (Jersey Cross)',
                'breed_id' => $breedJersey->id,
                'pen_id' => $penCowMilking->id,
                'status' => 'sick',
                'parity' => 2,
                'weight' => 415.0,
                'birth_date' => Carbon::now()->subMonths(38),
                'acq_date' => Carbon::now()->subMonths(18),
                'eid' => '982000001005',
                'qr' => 'FMS-COW-005-QR',
                'notes' => 'Under active antibiotic treatment for subclinical mastitis. MILK WITHDRAWAL ACTIVE!',
            ],
        ];

        $cows = [];
        foreach ($cowsData as $item) {
            $animal = Animal::create([
                'organization_id' => $org->id,
                'farm_id' => $farm->id,
                'species_id' => $cattleSpecies->id,
                'breed_id' => $item['breed_id'],
                'pen_id' => $item['pen_id'],
                'tag_number' => $item['tag'],
                'electronic_id' => $item['eid'],
                'qr_code_identifier' => $item['qr'],
                'name' => $item['name'],
                'sex' => 'female',
                'birth_date' => $item['birth_date'],
                'birth_weight_kg' => 32.0,
                'current_weight_kg' => $item['weight'],
                'acquisition_type' => 'purchased',
                'acquisition_date' => $item['acq_date'],
                'status' => $item['status'],
                'parity' => $item['parity'],
                'notes' => $item['notes'],
                'created_by' => $owner->id,
            ]);

            WeightRecord::create([
                'animal_id' => $animal->id,
                'weight_kg' => $item['weight'],
                'recorded_at' => Carbon::now()->subDays(10),
                'recorded_by_name' => $worker->name,
                'body_condition_score' => 3.5,
            ]);

            $cows[] = $animal;
        }

        // 10 Goats:
        $goatsData = [
            ['tag' => 'PK-GT-001', 'name' => 'Heera (Beetal Champion)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 2, 'weight' => 54.0, 'notes' => '3.2L daily, twin born, robust frame.'],
            ['tag' => 'PK-GT-002', 'name' => 'Moti (Beetal Spotted)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 1, 'weight' => 48.0, 'notes' => 'First lactation, 2.7L daily.'],
            ['tag' => 'PK-GT-003', 'name' => 'Noori (Kamori Long-eared)', 'breed_id' => $breedKamori->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 2, 'weight' => 52.0, 'notes' => 'Exceptional pedigree, 3.4L daily, high milk fat.'],
            ['tag' => 'PK-GT-004', 'name' => 'Kajal (Kamori Black)', 'breed_id' => $breedKamori->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 1, 'weight' => 46.0, 'notes' => 'Good appetite, 2.8L daily.'],
            ['tag' => 'PK-GT-005', 'name' => 'Shehzadi (DDP Cross)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatDry->id, 'status' => 'pregnant', 'parity' => 3, 'weight' => 58.0, 'notes' => 'Confirmed pregnant (twins) via ultrasound. Kidding expected in 14 days.'],
            ['tag' => 'PK-GT-006', 'name' => 'Chandni (Beetal White)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 1, 'weight' => 47.0, 'notes' => 'Consistent morning and evening milker.'],
            ['tag' => 'PK-GT-007', 'name' => 'Pari (Saanen Dairy)', 'breed_id' => $breedSaanen->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 2, 'weight' => 61.0, 'notes' => 'High volume goat (3.8L daily), gentle milker.'],
            ['tag' => 'PK-GT-008', 'name' => 'Sona (Beetal Brown)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'status' => 'lactating', 'parity' => 2, 'weight' => 53.0, 'notes' => 'Good teat conformation.'],
            ['tag' => 'PK-GT-009', 'name' => 'Laila (Kamori Dairy)', 'breed_id' => $breedKamori->id, 'pen' => $penGoatDry->id, 'status' => 'dry', 'parity' => 3, 'weight' => 55.0, 'notes' => 'Dry resting period.'],
            ['tag' => 'PK-GT-010', 'name' => 'Resham (Beetal Maiden)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatDry->id, 'status' => 'active', 'parity' => 0, 'weight' => 39.0, 'notes' => 'Replacement doe, ready for first breeding.'],
        ];

        $goats = [];
        foreach ($goatsData as $idx => $item) {
            $num = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
            $goat = Animal::create([
                'organization_id' => $org->id,
                'farm_id' => $farm->id,
                'species_id' => $goatSpecies->id,
                'breed_id' => $item['breed_id'],
                'pen_id' => $item['pen'],
                'tag_number' => $item['tag'],
                'electronic_id' => "982000002{$num}",
                'qr_code_identifier' => "FMS-GT-{$num}-QR",
                'name' => $item['name'],
                'sex' => 'female',
                'birth_date' => Carbon::now()->subMonths(rand(18, 36)),
                'birth_weight_kg' => 3.5,
                'current_weight_kg' => $item['weight'],
                'acquisition_type' => 'purchased',
                'acquisition_date' => Carbon::now()->subMonths(rand(6, 12)),
                'status' => $item['status'],
                'parity' => $item['parity'],
                'notes' => $item['notes'],
                'created_by' => $owner->id,
            ]);

            WeightRecord::create([
                'animal_id' => $goat->id,
                'weight_kg' => $item['weight'],
                'recorded_at' => Carbon::now()->subDays(15),
                'recorded_by_name' => $worker->name,
                'body_condition_score' => 3.2,
            ]);

            $goats[] = $goat;
        }

        // 7. Active Health Case & Treatment on COW-005 (Demonstrating Withdrawal Enforcement)
        $sickCow = $cows[4]; // PK-COW-005 (Sundri)
        $healthCase = HealthCase::create([
            'farm_id' => $farm->id,
            'animal_id' => $sickCow->id,
            'case_number' => 'HC-2026-0041',
            'symptom_observed_at' => Carbon::now()->subDays(1),
            'diagnosis' => 'Subclinical Mastitis (Right Hind Quarter)',
            'symptoms_description' => 'Slight swelling, California Mastitis Test (CMT) positive (2+), elevated somatic cell count.',
            'severity' => 'moderate',
            'status' => 'under_treatment',
            'attending_vet_name' => $vet->name,
            'created_by' => $vet->id,
        ]);

        Treatment::create([
            'farm_id' => $farm->id,
            'animal_id' => $sickCow->id,
            'health_case_id' => $healthCase->id,
            'medicine_id' => $amox->id,
            'administered_at' => Carbon::now()->subHours(18),
            'dosage' => 20.0,
            'dosage_unit' => 'ml',
            'route' => 'Intramuscular',
            'milk_withdrawal_until' => Carbon::now()->addDays(2)->addHours(6),
            'meat_withdrawal_until' => Carbon::now()->addDays(13),
            'administered_by' => $vet->name,
            'cost' => 1250.0,
            'notes' => 'First dose administered. Quarter stripped clean. Strict withdrawal enforced: DO NOT POOL MILK.',
        ]);

        // Vaccinations
        foreach ($cows as $c) {
            Vaccination::create([
                'farm_id' => $farm->id,
                'animal_id' => $c->id,
                'vaccine_name' => 'Aftovaxpur FMD Oil Adjuvant Vaccine',
                'batch_lot_number' => 'AFTO-2026-X8',
                'administered_date' => Carbon::now()->subMonths(2),
                'booster_due_date' => Carbon::now()->addMonths(4),
                'disease_targeted' => 'Foot and Mouth Disease (FMD)',
                'administered_by' => $vet->name,
            ]);
        }

        // 8. Breeding & Pregnancy records
        // COW-004 (Malka) advanced pregnancy
        $cow4 = $cows[3];
        $aiEvent = BreedingEvent::create([
            'farm_id' => $farm->id,
            'animal_id' => $cow4->id,
            'method' => 'artificial_insemination',
            'semen_straw_code' => 'SEMEN-SAH-PRIME-09',
            'sire_breed_code' => 'SAH',
            'insemination_datetime' => Carbon::now()->subDays(261),
            'technician_name' => 'Dr. Farooq (L&DD Inseminator)',
            'cost' => 2500.0,
            'status' => 'conceived',
            'notes' => 'Prime Sahiwal semen used from Semen Production Unit Qadirabad.',
        ]);

        Pregnancy::create([
            'farm_id' => $farm->id,
            'animal_id' => $cow4->id,
            'breeding_event_id' => $aiEvent->id,
            'check_date' => Carbon::now()->subDays(200),
            'method' => 'palpation',
            'status' => 'confirmed_pregnant',
            'expected_delivery_date' => Carbon::now()->addDays(22),
            'expected_dry_off_date' => Carbon::now()->subDays(38),
            'checked_by' => $vet->name,
            'notes' => 'Single calf palpable, active fetal movement, cow dried off properly.',
        ]);

        // GOAT-005 (Shehzadi) pregnancy
        $gt5 = $goats[4];
        Pregnancy::create([
            'farm_id' => $farm->id,
            'animal_id' => $gt5->id,
            'check_date' => Carbon::now()->subDays(60),
            'method' => 'ultrasound',
            'status' => 'confirmed_pregnant',
            'expected_delivery_date' => Carbon::now()->addDays(14),
            'checked_by' => $vet->name,
            'notes' => 'Twin pregnancy confirmed by portable ultrasound.',
        ]);

        // 9. Milk Recording (Sessions & Records for the last 5 days)
        $dailyCowYields = [
            'PK-COW-001' => [7.5, 6.8],
            'PK-COW-002' => [9.2, 8.4],
            'PK-COW-003' => [11.5, 10.3],
            'PK-COW-005' => [5.0, 4.8], // Under withdrawal!
        ];

        $dailyGoatYields = [
            'PK-GT-001' => [1.7, 1.5],
            'PK-GT-002' => [1.4, 1.3],
            'PK-GT-003' => [1.8, 1.6],
            'PK-GT-004' => [1.5, 1.3],
            'PK-GT-006' => [1.3, 1.2],
            'PK-GT-007' => [2.1, 1.8],
            'PK-GT-008' => [1.5, 1.4],
        ];

        for ($day = 4; $day >= 0; $day--) {
            $recordDate = Carbon::now()->subDays($day)->toDateString();

            foreach (['morning', 'evening'] as $shiftIdx => $shift) {
                $session = MilkSession::create([
                    'farm_id' => $farm->id,
                    'session_date' => $recordDate,
                    'shift' => $shift,
                    'total_yield_liters' => 0,
                    'bulk_tank_temperature_c' => 4.2,
                    'milker_id' => $worker->id,
                ]);

                $sessionTotal = 0;

                // Log cows
                foreach ($cows as $c) {
                    if (isset($dailyCowYields[$c->tag_number])) {
                        $yield = $dailyCowYields[$c->tag_number][$shiftIdx];
                        $isWithdrawn = ($c->tag_number === 'PK-COW-005' && $day <= 1);
                        $status = $isWithdrawn ? 'discarded_withdrawal' : 'premium';

                        MilkRecord::create([
                            'farm_id' => $farm->id,
                            'animal_id' => $c->id,
                            'milk_session_id' => $session->id,
                            'recorded_date' => $recordDate,
                            'shift' => $shift,
                            'yield_liters' => $yield,
                            'fat_percentage' => $c->breed->name === 'Sahiwal' ? 4.8 : 3.8,
                            'snf_percentage' => 8.9,
                            'protein_percentage' => 3.4,
                            'scc' => $isWithdrawn ? 680 : 120,
                            'temperature_c' => 36.5,
                            'quality_status' => $status,
                            'operator_notes' => $isWithdrawn ? 'Discarded down the drain due to active mastitis treatment' : 'Clean bulk milk sample',
                            'recorded_by' => $worker->id,
                        ]);

                        if (! $isWithdrawn) {
                            $sessionTotal += $yield;
                        }
                    }
                }

                // Log goats
                foreach ($goats as $g) {
                    if (isset($dailyGoatYields[$g->tag_number])) {
                        $yield = $dailyGoatYields[$g->tag_number][$shiftIdx];

                        MilkRecord::create([
                            'farm_id' => $farm->id,
                            'animal_id' => $g->id,
                            'milk_session_id' => $session->id,
                            'recorded_date' => $recordDate,
                            'shift' => $shift,
                            'yield_liters' => $yield,
                            'fat_percentage' => 4.2,
                            'snf_percentage' => 9.1,
                            'protein_percentage' => 3.6,
                            'scc' => 90,
                            'temperature_c' => 37.0,
                            'quality_status' => 'premium',
                            'recorded_by' => $worker->id,
                        ]);

                        $sessionTotal += $yield;
                    }
                }

                $session->update(['total_yield_liters' => $sessionTotal]);
            }
        }

        // 10. Feed Daily Consumption
        FeedConsumption::create([
            'farm_id' => $farm->id,
            'feed_item_id' => $silage->id,
            'pen_id' => $penCowMilking->id,
            'consumption_date' => Carbon::now()->toDateString(),
            'quantity_consumed' => 120.0,
            'unit_cost' => 16.50,
            'total_cost' => 120.0 * 16.50,
            'recorded_by' => $worker->id,
            'notes' => '30kg per cow mixed with concentrate.',
        ]);

        FeedConsumption::create([
            'farm_id' => $farm->id,
            'feed_item_id' => $concentrate->id,
            'pen_id' => $penCowMilking->id,
            'consumption_date' => Carbon::now()->toDateString(),
            'quantity_consumed' => 32.0,
            'unit_cost' => 88.00,
            'total_cost' => 32.0 * 88.00,
            'recorded_by' => $worker->id,
            'notes' => '8kg per in-milk cow divided between morning and evening milking.',
        ]);

        FeedConsumption::create([
            'farm_id' => $farm->id,
            'feed_item_id' => $goatFeed->id,
            'pen_id' => $penGoatMilking->id,
            'consumption_date' => Carbon::now()->toDateString(),
            'quantity_consumed' => 8.0,
            'unit_cost' => 75.00,
            'total_cost' => 8.0 * 75.00,
            'recorded_by' => $worker->id,
            'notes' => 'Daily concentrate ration for milking does.',
        ]);

        // 11. Climate & THI Real-time Reading
        $temp = 31.8;
        $rh = 64.0;
        $thi = ClimateReading::calculateTHI($temp, $rh);
        ClimateReading::create([
            'farm_id' => $farm->id,
            'barn_id' => $cattleBarn->id,
            'recorded_at' => Carbon::now()->subMinutes(15),
            'temperature_c' => $temp,
            'relative_humidity_percent' => $rh,
            'thi_index' => $thi,
            'heat_stress_level' => ClimateReading::determineHeatStressLevel($thi),
            'sensor_device_id' => 'IOT-ENV-01',
            'mitigation_action_taken' => 'Automatic high-velocity ventilation fans active (Zone A & B). Misting scheduled for 14:00.',
        ]);

        // 12. Suppliers & Finance
        $feedSupplier = Supplier::create([
            'farm_id' => $farm->id,
            'name' => 'Punjab Corn Silage & Agrico',
            'contact_person' => 'Malik Shakeel',
            'phone' => '+92-321-4567890',
            'category' => 'feed',
            'address' => 'Depalpur Bypass, Okara',
        ]);

        // Income from Milk
        FinancialTransaction::create([
            'farm_id' => $farm->id,
            'type' => 'income',
            'category' => 'milk_sale',
            'amount' => 31200.0,
            'transaction_date' => Carbon::now()->subDays(2),
            'reference_number' => 'INV-MILK-2026-08',
            'payment_method' => 'bank_transfer',
            'description' => 'Supply of 160L pooled cow and goat milk to local processing collection unit.',
            'recorded_by' => $owner->id,
        ]);

        // Feed purchase expense
        FinancialTransaction::create([
            'farm_id' => $farm->id,
            'type' => 'expense',
            'category' => 'feed_purchase',
            'amount' => 45000.0,
            'transaction_date' => Carbon::now()->subDays(5),
            'reference_number' => 'EXP-FEED-092',
            'supplier_id' => $feedSupplier->id,
            'payment_method' => 'cash',
            'description' => 'Purchase of 3000kg Corn Silage bunker delivery.',
            'recorded_by' => $owner->id,
        ]);

        // Vet medicine expense
        FinancialTransaction::create([
            'farm_id' => $farm->id,
            'type' => 'expense',
            'category' => 'veterinary_medicine',
            'amount' => 4200.0,
            'transaction_date' => Carbon::now()->subDays(1),
            'reference_number' => 'VET-MED-012',
            'payment_method' => 'jazzcash',
            'description' => 'Veterinary visit, mastitis diagnostics, and medicine stock renewal.',
            'recorded_by' => $owner->id,
        ]);

        // 13. Operational Tasks & Reminders
        FarmTask::create([
            'farm_id' => $farm->id,
            'title' => 'Morning Milking Routine (Cows & Goats)',
            'description' => 'Ensure teat dipping before and after milking. Discard milk from PK-COW-005 separately.',
            'category' => 'milking',
            'priority' => 'high',
            'due_date' => Carbon::now()->toDateString(),
            'due_time' => '05:30:00',
            'status' => 'completed',
            'assigned_to' => $worker->id,
            'completed_at' => Carbon::now()->subHours(6),
            'completion_notes' => 'Morning shift completed. 44.5L cow milk + 11.2L goat milk harvested.',
        ]);

        FarmTask::create([
            'farm_id' => $farm->id,
            'title' => 'Administer Follow-up Treatment to PK-COW-005',
            'description' => 'Veterinarian check on right hind quarter. Measure temperature and administer prescribed dose if indicated.',
            'category' => 'veterinary',
            'priority' => 'urgent',
            'due_date' => Carbon::now()->toDateString(),
            'due_time' => '16:00:00',
            'animal_id' => $sickCow->id,
            'status' => 'pending',
            'assigned_to' => $vet->id,
        ]);

        FarmTask::create([
            'farm_id' => $farm->id,
            'title' => 'Prepare Maternity Pen for PK-COW-004 (Malka)',
            'description' => 'Sanitize pen with lime wash, lay fresh wheat straw bedding for expected delivery.',
            'category' => 'cleaning_biosecurity',
            'priority' => 'medium',
            'due_date' => Carbon::now()->addDays(5)->toDateString(),
            'animal_id' => $cow4->id,
            'status' => 'pending',
            'assigned_to' => $worker->id,
        ]);

        FarmTask::create([
            'farm_id' => $farm->id,
            'title' => 'Re-order Dairy Wanta Concentrate',
            'description' => 'Stock is currently at 1100kg. Order next 2-ton batch to ensure continuous feeding.',
            'category' => 'feeding',
            'priority' => 'medium',
            'due_date' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'pending',
            'assigned_to' => $owner->id,
        ]);
    }
}
