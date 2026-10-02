<?php

namespace Database\Seeders;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\AnimalBcsRecord;
use App\Domain\Animals\Models\AnimalGroup;
use App\Domain\Animals\Models\AnimalGroupMembership;
use App\Domain\Animals\Models\AnimalIdentifier;
use App\Domain\Animals\Models\AnimalPedigree;
use App\Domain\Animals\Models\Breed;
use App\Domain\Animals\Models\FeedlotRecord;
use App\Domain\Animals\Models\FleeceRecord;
use App\Domain\Animals\Models\SpecializedSpeciesAttribute;
use App\Domain\Animals\Models\Species;
use App\Domain\Animals\Models\WeightRecord;
use App\Domain\Animals\Services\AverageDailyGainCalculator;
use App\Domain\Breeding\Models\Birth;
use App\Domain\Breeding\Models\BreedingEvent;
use App\Domain\Breeding\Models\PostpartumCheck;
use App\Domain\Breeding\Models\Pregnancy;
use App\Domain\Breeding\Models\SemenStrawInventory;
use App\Domain\Climate\Models\ClimateReading;
use App\Domain\Climate\Models\GrazingLog;
use App\Domain\Climate\Models\PasturePlot;
use App\Domain\Compliance\Models\AnimalMovementPermit;
use App\Domain\Compliance\Models\AnimalWelfareAssessment;
use App\Domain\Compliance\Models\CompliancePack;
use App\Domain\Compliance\Models\HalalSlaughterCertification;
use App\Domain\Feed\Models\FeedBunkScore;
use App\Domain\Feed\Models\FeedConsumption;
use App\Domain\Feed\Models\FeedFormulation;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Feed\Models\SilageBunker;
use App\Domain\Feed\Models\TmrBatch;
use App\Domain\Feed\Services\RationFormulationOptimizerService;
use App\Domain\Finance\Models\BiologicalAssetValuation;
use App\Domain\Finance\Models\ChartOfAccount;
use App\Domain\Finance\Models\CostAllocationRule;
use App\Domain\Finance\Models\FinancialTransaction;
use App\Domain\Finance\Models\GeneralLedgerEntry;
use App\Domain\Finance\Models\Supplier;
use App\Domain\Health\Models\BiosecurityAudit;
use App\Domain\Health\Models\HealthCase;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Models\Treatment;
use App\Domain\Health\Models\Vaccination;
use App\Domain\Inventory\Models\FarmAsset;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryTransaction;
use App\Domain\Inventory\Models\MaintenanceLog;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Milk\Models\BulkTank;
use App\Domain\Milk\Models\FarmerSupplier;
use App\Domain\Milk\Models\MilkCollectionCenter;
use App\Domain\Milk\Models\MilkCollectionIntake;
use App\Domain\Milk\Models\MilkDispatch;
use App\Domain\Milk\Models\MilkRateChart;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Milk\Models\MilkSession;
use App\Domain\Milk\Services\TwoDimensionalRateChartPricingCalculator;
use App\Domain\Organization\Models\AuditLog;
use App\Domain\Organization\Models\Barn;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\FarmStructure;
use App\Domain\Organization\Models\FarmZone;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\Pen;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\Role;
use App\Domain\Organization\Models\UserFarmAccess;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\CustomerSubscription;
use App\Domain\Sales\Models\CustomerWalletTransaction;
use App\Domain\Sales\Models\DeliveryRun;
use App\Domain\Sales\Models\DeliveryRunStop;
use App\Domain\Sync\Models\DeviceRegistry;
use App\Domain\Sync\Models\DeviceTelemetryLog;
use App\Domain\Sync\Models\SyncChangeLog;
use App\Domain\Sync\Models\SyncClientRegistry;
use App\Domain\Workforce\Models\FarmTask;
use App\Models\User;
use App\Models\UserPreference;
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

        $auditor = User::firstOrCreate(
            ['email' => 'auditor@livestock.gov.pk'],
            [
                'name' => 'Dr. Khalid Hussain (Govt Inspector)',
                'password' => Hash::make('password'),
            ]
        );

        // 1.1 User Preferences
        UserPreference::updateOrCreate(
            ['user_id' => $owner->id],
            [
                'locale' => 'en',
                'timezone' => 'Asia/Karachi',
                'date_format' => 'd M Y',
                'time_format' => '12h',
                'currency' => 'PKR',
                'currency_symbol_position' => 'before',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
            ]
        );

        UserPreference::updateOrCreate(
            ['user_id' => $worker->id],
            [
                'locale' => 'ur',
                'timezone' => 'Asia/Karachi',
                'date_format' => 'd-m-Y',
                'time_format' => '12h',
                'currency' => 'PKR',
                'currency_symbol_position' => 'before',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
            ]
        );

        UserPreference::updateOrCreate(
            ['user_id' => $auditor->id],
            [
                'locale' => 'ar',
                'timezone' => 'Asia/Dubai',
                'date_format' => 'Y-m-d',
                'time_format' => '24h',
                'currency' => 'AED',
                'currency_symbol_position' => 'before',
                'decimal_separator' => '.',
                'thousands_separator' => ',',
            ]
        );

        // 2. Organization & Farm
        $org = Organization::create([
            'name' => 'GreenPastures Agro & Dairy',
            'slug' => 'greenpastures-agro',
            'tier' => 'commercial',
            'status' => 'active',
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
            'operating_profile' => [
                'species' => ['cattle', 'goat', 'buffalo'],
                'primary_currency' => 'PKR',
                'metric_system' => true,
                'date_format' => 'Y-m-d',
                'fiscal_year_start' => 7,
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
            'elevation_meters' => 218,
            'soil_type' => 'Loam / Sandy Loam',
            'total_area' => 20.00,
            'area_unit' => 'acres',
            'timezone' => 'Asia/Karachi',
            'water_sources' => ['Deep Tube-well (Solar Powered)', 'Canal Water Allocation', 'Rainwater Recovery Pond'],
            'backup_generator' => true,
            'boundary_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [[74.4490, 31.1160], [74.4520, 31.1160], [74.4520, 31.1185], [74.4490, 31.1185], [74.4490, 31.1160]],
                ],
            ],
            'climate_settings' => [
                'cooling_fans_threshold_c' => 28.0,
                'misting_thi_threshold' => 76.0,
            ],
        ]);

        // 3. RBAC Roles & Permissions
        $permissionsList = [
            // Livestock
            ['name' => 'animals.view', 'category' => 'livestock', 'description' => 'View animals and records'],
            ['name' => 'animals.create', 'category' => 'livestock', 'description' => 'Register new animal or birth'],
            ['name' => 'animals.update', 'category' => 'livestock', 'description' => 'Update animal profile and movements'],
            ['name' => 'animals.delete', 'category' => 'livestock', 'description' => 'Decommission or cull animal'],
            // Milk
            ['name' => 'milk.record', 'category' => 'milk_production', 'description' => 'Log milking session yields'],
            ['name' => 'milk.approve', 'category' => 'milk_production', 'description' => 'Approve milk batch for sale'],
            ['name' => 'milk.override_lock', 'category' => 'milk_production', 'description' => 'Supervisor override on withdrawal lock'],
            // Health
            ['name' => 'health.diagnose', 'category' => 'veterinary', 'description' => 'Diagnose and open health case'],
            ['name' => 'health.administer', 'category' => 'veterinary', 'description' => 'Administer medicine or vaccine'],
            // Feed & Inventory
            ['name' => 'feed.manage', 'category' => 'feed', 'description' => 'Manage rations and consumption'],
            // Finance
            ['name' => 'finance.view', 'category' => 'finance', 'description' => 'View financial dashboards'],
            ['name' => 'finance.transact', 'category' => 'finance', 'description' => 'Record revenue and expenses'],
            // Compliance & Audit
            ['name' => 'audit.view', 'category' => 'compliance', 'description' => 'Inspect logs and traceability trails'],
        ];

        $createdPermissions = [];
        foreach ($permissionsList as $pData) {
            $createdPermissions[$pData['name']] = Permission::create($pData);
        }

        // Roles
        $roleOwner = Role::create([
            'organization_id' => $org->id,
            'name' => 'Farm Owner',
            'slug' => 'farm_owner',
            'description' => 'Complete control over farm enterprise',
            'is_system' => true,
        ]);
        $roleOwner->permissions()->attach(array_values(array_map(fn ($p) => $p->id, $createdPermissions)));

        $roleVet = Role::create([
            'organization_id' => $org->id,
            'name' => 'Consulting Veterinarian',
            'slug' => 'veterinarian',
            'description' => 'Clinical diagnostics, treatments, and biosecurity oversight',
            'is_system' => true,
        ]);
        $roleVet->permissions()->attach([
            $createdPermissions['animals.view']->id,
            $createdPermissions['health.diagnose']->id,
            $createdPermissions['health.administer']->id,
            $createdPermissions['audit.view']->id,
        ]);

        $roleManager = Role::create([
            'organization_id' => $org->id,
            'name' => 'Herd Manager',
            'slug' => 'herd_manager',
            'description' => 'Day-to-day operations, feeding, and milking',
            'is_system' => true,
        ]);
        $roleManager->permissions()->attach([
            $createdPermissions['animals.view']->id,
            $createdPermissions['animals.create']->id,
            $createdPermissions['animals.update']->id,
            $createdPermissions['milk.record']->id,
            $createdPermissions['feed.manage']->id,
        ]);

        $roleAuditor = Role::create([
            'organization_id' => null, // Global regulatory role
            'name' => 'Government Veterinary Auditor',
            'slug' => 'auditor',
            'description' => 'Biosecurity, antibiotic withdrawal, and animal welfare compliance inspection',
            'is_system' => true,
        ]);
        $roleAuditor->permissions()->attach([
            $createdPermissions['animals.view']->id,
            $createdPermissions['audit.view']->id,
        ]);

        // Delegated Access Grants
        UserFarmAccess::create([
            'user_id' => $owner->id,
            'farm_id' => $farm->id,
            'role_id' => $roleOwner->id,
            'access_level' => 'full',
            'granted_at' => Carbon::now()->subMonths(6),
            'is_active' => true,
            'reason' => 'Farm founder and proprietor',
        ]);

        UserFarmAccess::create([
            'user_id' => $worker->id,
            'farm_id' => $farm->id,
            'role_id' => $roleManager->id,
            'access_level' => 'operational',
            'granted_by' => $owner->id,
            'granted_at' => Carbon::now()->subMonths(6),
            'is_active' => true,
            'reason' => 'Herd Operations Lead',
        ]);

        UserFarmAccess::create([
            'user_id' => $vet->id,
            'farm_id' => $farm->id,
            'role_id' => $roleVet->id,
            'access_level' => 'veterinary_only',
            'granted_by' => $owner->id,
            'granted_at' => Carbon::now()->subMonths(3),
            'expires_at' => Carbon::now()->addMonths(9),
            'is_active' => true,
            'reason' => 'Annual Veterinary Care Service Retainer',
        ]);

        UserFarmAccess::create([
            'user_id' => $auditor->id,
            'farm_id' => $farm->id,
            'role_id' => $roleAuditor->id,
            'access_level' => 'read_only',
            'granted_by' => $owner->id,
            'granted_at' => Carbon::now()->subDays(2),
            'expires_at' => Carbon::now()->addDays(5), // 7-day temporary access window
            'is_active' => true,
            'reason' => 'Punjab Food Authority Dairy & Antibiotic Inspection',
        ]);

        // 4. Farm Spatial Zones
        $zoneCompound = FarmZone::create([
            'farm_id' => $farm->id,
            'name' => 'Central Facility & Housing Compound',
            'code' => 'ZONE-HQ',
            'type' => 'facility_compound',
            'area_size' => 3.50,
            'area_unit' => 'acres',
            'soil_type' => 'Compacted Gravel / Concrete',
            'status' => 'active',
            'notes' => 'Barns, milking parlor, feed storage bunkers, office',
        ]);

        $zonePastureA = FarmZone::create([
            'farm_id' => $farm->id,
            'name' => 'North Pasture Paddock A (Rhodes Grass)',
            'code' => 'PASTURE-A',
            'type' => 'pasture',
            'area_size' => 8.00,
            'area_unit' => 'acres',
            'soil_type' => 'Loam',
            'irrigation_type' => 'sprinkler',
            'status' => 'active',
            'notes' => 'Rotational grazing pasture for milking cattle',
        ]);

        $zonePastureB = FarmZone::create([
            'farm_id' => $farm->id,
            'name' => 'South Pasture Paddock B (Alfalfa & Berseem)',
            'code' => 'PASTURE-B',
            'type' => 'crop_field',
            'area_size' => 6.50,
            'area_unit' => 'acres',
            'soil_type' => 'Sandy Loam',
            'irrigation_type' => 'flood',
            'status' => 'active',
            'notes' => 'High protein green fodder production',
        ]);

        $zoneQuarantine = FarmZone::create([
            'farm_id' => $farm->id,
            'name' => 'Bio-Secure Quarantine Zone',
            'code' => 'ZONE-QUAR',
            'type' => 'quarantine_zone',
            'area_size' => 1.00,
            'area_unit' => 'acres',
            'status' => 'quarantine',
            'notes' => 'Isolated perimeter for new arrivals and sick livestock',
        ]);

        // 5. Farm Structures Hierarchy
        $structCattleBarn = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneCompound->id,
            'name' => 'Main Cattle Free-Stall Barn',
            'code' => 'BARN-01',
            'structure_type' => 'barn',
            'target_species' => 'cattle',
            'capacity' => 20,
            'area_sq_meters' => 450.00,
            'ventilation_type' => 'tunnel_fans',
            'has_automated_feeders' => true,
            'has_automated_waterers' => true,
            'has_misting_cooling' => true,
            'current_headcount' => 5,
        ]);

        $structPenC1 = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneCompound->id,
            'parent_structure_id' => $structCattleBarn->id,
            'name' => 'Milking Cattle Pen A',
            'code' => 'PEN-C-01',
            'structure_type' => 'pen',
            'target_species' => 'cattle',
            'capacity' => 12,
            'area_sq_meters' => 220.00,
            'current_headcount' => 3,
        ]);

        $structPenC2 = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneCompound->id,
            'parent_structure_id' => $structCattleBarn->id,
            'name' => 'Dry & Close-Up Calving Pen',
            'code' => 'PEN-C-02',
            'structure_type' => 'maternity_pen',
            'target_species' => 'cattle',
            'capacity' => 4,
            'area_sq_meters' => 100.00,
            'current_headcount' => 1,
        ]);

        $structPenHospital = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneQuarantine->id,
            'parent_structure_id' => $structCattleBarn->id,
            'name' => 'Cattle Isolation & Hospital Stall',
            'code' => 'STALL-HOSP-01',
            'structure_type' => 'isolation_ward',
            'target_species' => 'cattle',
            'capacity' => 2,
            'area_sq_meters' => 45.00,
            'current_headcount' => 1,
        ]);

        $structGoatShed = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneCompound->id,
            'name' => 'Elevated Slatted-Floor Goat Shed',
            'code' => 'SHED-GOAT-01',
            'structure_type' => 'shed',
            'target_species' => 'goat',
            'capacity' => 35,
            'area_sq_meters' => 260.00,
            'ventilation_type' => 'open_air',
            'has_automated_waterers' => true,
            'current_headcount' => 10,
        ]);

        $structPenG1 = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneCompound->id,
            'parent_structure_id' => $structGoatShed->id,
            'name' => 'Milking Does Pen G1',
            'code' => 'PEN-G-01',
            'structure_type' => 'pen',
            'target_species' => 'goat',
            'capacity' => 18,
            'area_sq_meters' => 130.00,
            'current_headcount' => 7,
        ]);

        $structPenG2 = FarmStructure::create([
            'farm_id' => $farm->id,
            'zone_id' => $zoneCompound->id,
            'parent_structure_id' => $structGoatShed->id,
            'name' => 'Dry Does & Pregnant Pen G2',
            'code' => 'PEN-G-02',
            'structure_type' => 'pen',
            'target_species' => 'goat',
            'capacity' => 15,
            'area_sq_meters' => 110.00,
            'current_headcount' => 3,
        ]);

        // Legacy Barns & Pens (kept for backward compatibility)
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

        // 6. Species & Breeds
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

        // 7. Feeds Catalog
        $silage = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Corn Silage (Pioneer)',
            'code' => 'SIL-01',
            'category' => 'silage',
            'unit' => 'kg',
            'current_stock' => 14200.0,
            'minimum_stock_alert' => 2000.0,
            'cost_per_unit' => 16.50,
            'dry_matter_percentage' => 34.0,
            'crude_protein_percentage' => 8.5,
            'ndf_percentage' => 44.0,
            'adf_percentage' => 26.0,
            'nel_mcal_per_kg' => 1.55,
            'tdn_percentage' => 68.0,
            'calcium_percentage' => 0.28,
            'phosphorus_percentage' => 0.22,
            'ash_percentage' => 4.5,
            'is_active' => true,
        ]);

        $alfalfaHay = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Alfalfa Hay Premium (Lucerne)',
            'code' => 'ALF-01',
            'category' => 'hay_dry',
            'unit' => 'kg',
            'current_stock' => 5500.0,
            'minimum_stock_alert' => 1000.0,
            'cost_per_unit' => 42.00,
            'dry_matter_percentage' => 88.0,
            'crude_protein_percentage' => 19.5,
            'ndf_percentage' => 40.0,
            'adf_percentage' => 30.0,
            'nel_mcal_per_kg' => 1.40,
            'tdn_percentage' => 60.0,
            'calcium_percentage' => 1.35,
            'phosphorus_percentage' => 0.25,
            'ash_percentage' => 8.5,
            'is_active' => true,
        ]);

        $concentrate = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Dairy Wanta 18% CP High Milk',
            'code' => 'WNT-18',
            'category' => 'concentrate',
            'unit' => 'kg',
            'current_stock' => 3100.0,
            'minimum_stock_alert' => 500.0,
            'cost_per_unit' => 88.00,
            'dry_matter_percentage' => 89.0,
            'crude_protein_percentage' => 18.2,
            'ndf_percentage' => 28.0,
            'adf_percentage' => 14.0,
            'nel_mcal_per_kg' => 1.82,
            'tdn_percentage' => 76.0,
            'calcium_percentage' => 0.90,
            'phosphorus_percentage' => 0.55,
            'ash_percentage' => 6.0,
            'is_active' => true,
        ]);

        $cottonseedCake = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Cottonseed Cake (Khal Benola)',
            'code' => 'KHAL-01',
            'category' => 'byproduct',
            'unit' => 'kg',
            'current_stock' => 2000.0,
            'minimum_stock_alert' => 400.0,
            'cost_per_unit' => 95.00,
            'dry_matter_percentage' => 91.0,
            'crude_protein_percentage' => 22.5,
            'ndf_percentage' => 42.0,
            'adf_percentage' => 28.0,
            'nel_mcal_per_kg' => 1.75,
            'tdn_percentage' => 72.0,
            'calcium_percentage' => 0.22,
            'phosphorus_percentage' => 0.95,
            'ash_percentage' => 5.8,
            'is_active' => true,
        ]);

        $goatFeed = FeedItem::create([
            'farm_id' => $farm->id,
            'name' => 'Goat High-Protein Ration Pellets',
            'code' => 'GT-PEL',
            'category' => 'concentrate',
            'unit' => 'kg',
            'current_stock' => 1420.0,
            'minimum_stock_alert' => 300.0,
            'cost_per_unit' => 75.00,
            'dry_matter_percentage' => 90.0,
            'crude_protein_percentage' => 16.5,
            'ndf_percentage' => 32.0,
            'adf_percentage' => 16.0,
            'nel_mcal_per_kg' => 1.68,
            'tdn_percentage' => 70.0,
            'calcium_percentage' => 0.85,
            'phosphorus_percentage' => 0.50,
            'ash_percentage' => 6.2,
            'is_active' => true,
        ]);

        // 8. Medicines Catalog
        $amox = Medicine::create([
            'organization_id' => $org->id,
            'name' => 'Amoxicillin Trihydrate 15%',
            'active_ingredient' => 'Amoxicillin',
            'category' => 'antibiotic',
            'is_antimicrobial' => true,
            'who_classification' => 'highly_important',
            'standard_ddda_mg_per_kg' => 15.0000,
            'default_dosage' => '15ml per 100kg body weight',
            'dosage_unit' => 'ml',
            'route_of_administration' => 'Intramuscular',
            'milk_withdrawal_days' => 3,
            'meat_withdrawal_days' => 14,
            'unit_cost' => 1250.00,
            'current_stock' => 8.0,
            'stock_unit' => 'vial_100ml',
        ]);

        $ceftiofur = Medicine::create([
            'organization_id' => $org->id,
            'name' => 'Ceftiofur Hydrochloride 5% (Excenel RTU)',
            'active_ingredient' => 'Ceftiofur HCl',
            'category' => 'antibiotic',
            'is_antimicrobial' => true,
            'who_classification' => 'critically_important',
            'standard_ddda_mg_per_kg' => 1.0000,
            'default_dosage' => '1ml per 50kg body weight',
            'dosage_unit' => 'ml',
            'route_of_administration' => 'Subcutaneous',
            'milk_withdrawal_days' => 0,
            'meat_withdrawal_days' => 4,
            'unit_cost' => 3800.00,
            'current_stock' => 5.0,
            'stock_unit' => 'vial_100ml',
        ]);

        $fmdVaccine = Medicine::create([
            'organization_id' => $org->id,
            'name' => 'Aftovaxpur FMD Oil Adjuvant Vaccine',
            'active_ingredient' => 'Inactivated FMD Virus (O, A, Asia-1)',
            'category' => 'vaccine',
            'is_antimicrobial' => false,
            'who_classification' => 'not_applicable',
            'default_dosage' => '2ml',
            'dosage_unit' => 'ml',
            'route_of_administration' => 'Subcutaneous',
            'milk_withdrawal_days' => 0,
            'meat_withdrawal_days' => 0,
            'unit_cost' => 450.00,
            'current_stock' => 25.0,
            'stock_unit' => 'dose',
        ]);

        // 9. Animal Groups Setup
        $groupHighYieldCows = AnimalGroup::create([
            'farm_id' => $farm->id,
            'name' => 'High-Yield Milking Cows (>18L)',
            'code' => 'GRP-COW-HIGH',
            'group_type' => 'production',
            'is_dynamic' => true,
            'criteria_rules' => ['min_yield' => 18.0, 'stage' => 'lactating'],
            'description' => 'Elite lactating cows receiving high-density TMR ration',
        ]);

        $groupDryCows = AnimalGroup::create([
            'farm_id' => $farm->id,
            'name' => 'Dry & Close-Up Calvers',
            'code' => 'GRP-COW-DRY',
            'group_type' => 'production',
            'is_dynamic' => false,
            'description' => 'Cows within 60 days of calving, negative DCAD diet',
        ]);

        $groupMilkingGoats = AnimalGroup::create([
            'farm_id' => $farm->id,
            'name' => 'Milking Beetal & Kamori Does',
            'code' => 'GRP-GT-MILK',
            'group_type' => 'production',
            'is_dynamic' => true,
            'description' => 'Active does in commercial dairy production',
        ]);

        $groupQuarantine = AnimalGroup::create([
            'farm_id' => $farm->id,
            'name' => 'Quarantine & Clinical Isolation',
            'code' => 'GRP-QUARANTINE',
            'group_type' => 'health',
            'is_dynamic' => false,
            'description' => 'Strict bio-secure holding with active withdrawal restrictions',
        ]);

        // 10. Pilot Animals: 5 Cows + 10 Goats
        $adgCalculator = new AverageDailyGainCalculator;

        $cowsData = [
            [
                'tag' => 'PK-COW-001',
                'name' => 'Gulabo (Pure Sahiwal)',
                'breed_id' => $breedSahiwal->id,
                'pen_id' => $penCowMilking->id,
                'structure_id' => $structPenC1->id,
                'zone_id' => $zoneCompound->id,
                'status' => 'lactating',
                'lifecycle_stage' => 'lactating',
                'parity' => 2,
                'weight' => 435.0,
                'birth_date' => Carbon::now()->subMonths(44),
                'acq_date' => Carbon::now()->subMonths(20),
                'eid' => '982000001001',
                'qr' => 'FMS-COW-001-QR',
                'genetic_merit' => 112.5,
                'sire_code' => 'SPU-SAH-504',
                'sire_name' => 'Rustam-e-Hind',
                'dam_name' => 'Gulab-1',
                'bcs' => 3.50,
                'locomotion' => 1,
                'notes' => 'High butterfat yield, placid temperament, calm machine milker.',
                'group' => $groupHighYieldCows->id,
            ],
            [
                'tag' => 'PK-COW-002',
                'name' => 'Rani (Sahiwal F1 Cross)',
                'breed_id' => $breedSahiwal->id,
                'pen_id' => $penCowMilking->id,
                'structure_id' => $structPenC1->id,
                'zone_id' => $zoneCompound->id,
                'status' => 'lactating',
                'lifecycle_stage' => 'lactating',
                'parity' => 3,
                'weight' => 460.0,
                'birth_date' => Carbon::now()->subMonths(52),
                'acq_date' => Carbon::now()->subMonths(24),
                'eid' => '982000001002',
                'qr' => 'FMS-COW-002-QR',
                'genetic_merit' => 108.0,
                'sire_code' => 'SPU-SAH-412',
                'sire_name' => 'Sher-e-Punjab',
                'dam_name' => 'Rani-Senior',
                'bcs' => 3.25,
                'locomotion' => 1,
                'notes' => 'Top producing indigenous cross, disease resistant.',
                'group' => $groupHighYieldCows->id,
            ],
            [
                'tag' => 'PK-COW-003',
                'name' => 'Chameli (Holstein Cross)',
                'breed_id' => $breedHF->id,
                'pen_id' => $penCowMilking->id,
                'structure_id' => $structPenC1->id,
                'zone_id' => $zoneCompound->id,
                'status' => 'lactating',
                'lifecycle_stage' => 'lactating',
                'parity' => 1,
                'weight' => 510.0,
                'birth_date' => Carbon::now()->subMonths(30),
                'acq_date' => Carbon::now()->subMonths(12),
                'eid' => '982000001003',
                'qr' => 'FMS-COW-003-QR',
                'genetic_merit' => 124.0,
                'sire_code' => 'ABS-29HO18920',
                'sire_name' => 'AltaZazzle',
                'dam_name' => 'Champa',
                'bcs' => 3.25,
                'locomotion' => 1,
                'notes' => 'First calver, high peak volume (22L/day), requires extra cooling.',
                'group' => $groupHighYieldCows->id,
            ],
            [
                'tag' => 'PK-COW-004',
                'name' => 'Malka (Sahiwal Proven)',
                'breed_id' => $breedSahiwal->id,
                'pen_id' => $penCowDry->id,
                'structure_id' => $structPenC2->id,
                'zone_id' => $zoneCompound->id,
                'status' => 'dry',
                'lifecycle_stage' => 'dry',
                'parity' => 4,
                'weight' => 475.0,
                'birth_date' => Carbon::now()->subMonths(66),
                'acq_date' => Carbon::now()->subMonths(36),
                'eid' => '982000001004',
                'qr' => 'FMS-COW-004-QR',
                'genetic_merit' => 115.0,
                'sire_code' => 'SPU-SAH-308',
                'sire_name' => 'Bahadur',
                'dam_name' => 'Malka-0',
                'bcs' => 3.75,
                'locomotion' => 1,
                'notes' => 'Dry-off period, advanced pregnancy, expected calving in 22 days.',
                'group' => $groupDryCows->id,
            ],
            [
                'tag' => 'PK-COW-005',
                'name' => 'Sundri (Jersey Cross)',
                'breed_id' => $breedJersey->id,
                'pen_id' => $penCowMilking->id,
                'structure_id' => $structPenHospital->id,
                'zone_id' => $zoneQuarantine->id,
                'status' => 'sick',
                'lifecycle_stage' => 'lactating',
                'parity' => 2,
                'weight' => 415.0,
                'birth_date' => Carbon::now()->subMonths(38),
                'acq_date' => Carbon::now()->subMonths(18),
                'eid' => '982000001005',
                'qr' => 'FMS-COW-005-QR',
                'genetic_merit' => 101.5,
                'sire_code' => 'JER-GEN-88',
                'sire_name' => 'Goldmine',
                'dam_name' => 'Sundri-M',
                'bcs' => 3.00,
                'locomotion' => 2, // Mild lameness / discomfort
                'is_quarantined' => true,
                'notes' => 'Under active antibiotic treatment for subclinical mastitis. MILK WITHDRAWAL ACTIVE!',
                'group' => $groupQuarantine->id,
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
                'structure_id' => $item['structure_id'],
                'zone_id' => $item['zone_id'],
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
                'lifecycle_stage' => $item['lifecycle_stage'],
                'parity' => $item['parity'],
                'genetic_merit_index' => $item['genetic_merit'],
                'is_quarantined' => $item['is_quarantined'] ?? false,
                'notes' => $item['notes'],
                'created_by' => $owner->id,
            ]);

            // Multi-tag Identifiers
            AnimalIdentifier::create([
                'animal_id' => $animal->id,
                'id_type' => 'rfid_iso11784',
                'id_value' => $item['eid'],
                'tag_color' => 'yellow',
                'tag_placement' => 'left_ear',
                'is_primary' => true,
                'applied_date' => $item['acq_date'],
            ]);

            AnimalIdentifier::create([
                'animal_id' => $animal->id,
                'id_type' => 'visual_ear_tag',
                'id_value' => $item['tag'],
                'tag_color' => 'yellow',
                'tag_placement' => 'right_ear',
                'is_primary' => false,
                'applied_date' => $item['acq_date'],
            ]);

            AnimalIdentifier::create([
                'animal_id' => $animal->id,
                'id_type' => 'qr_code',
                'id_value' => $item['qr'],
                'tag_color' => 'white',
                'tag_placement' => 'neck_collar',
                'is_primary' => false,
                'applied_date' => $item['acq_date'],
            ]);

            // Pedigree & Lineage
            AnimalPedigree::create([
                'animal_id' => $animal->id,
                'sire_code' => $item['sire_code'],
                'sire_name' => $item['sire_name'],
                'dam_name' => $item['dam_name'],
                'generation_depth' => 2,
                'inbreeding_coefficient' => 0.0150,
                'pedigree_tree_json' => [
                    'sire' => ['code' => $item['sire_code'], 'name' => $item['sire_name']],
                    'dam' => ['name' => $item['dam_name']],
                ],
            ]);

            // Sequential Growth Weights with ADG
            $prevWeight = $item['weight'] - 18.0;
            $adgCalculator->recordWeight(
                animal: $animal,
                weightKg: $prevWeight,
                recordedAt: Carbon::now()->subDays(40),
                weighingMethod: 'scale',
                recordedByUserId: $worker->id,
                notes: 'Routine monthly weigh-in'
            );

            $adgCalculator->recordWeight(
                animal: $animal,
                weightKg: $item['weight'],
                recordedAt: Carbon::now()->subDays(10),
                weighingMethod: 'scale',
                recordedByUserId: $worker->id,
                notes: 'Latest herd weigh-in'
            );

            // BCS & Locomotion Record
            AnimalBcsRecord::create([
                'animal_id' => $animal->id,
                'bcs_score' => $item['bcs'],
                'locomotion_score' => $item['locomotion'],
                'rumen_fill_score' => 4,
                'cleanliness_score' => 1,
                'assessed_at' => Carbon::now()->subDays(7),
                'assessed_by' => $vet->id,
                'notes' => 'Weekly veterinary assessment',
            ]);

            // Group Membership
            AnimalGroupMembership::create([
                'group_id' => $item['group'],
                'animal_id' => $animal->id,
                'joined_at' => Carbon::now()->subMonths(1),
                'is_current' => true,
                'reason' => 'Production cohort assignment',
            ]);

            // Legacy Weight Record (for backwards compatibility)
            WeightRecord::create([
                'animal_id' => $animal->id,
                'weight_kg' => $item['weight'],
                'recorded_at' => Carbon::now()->subDays(10),
                'recorded_by_name' => $worker->name,
                'body_condition_score' => $item['bcs'],
            ]);

            $cows[] = $animal;
        }

        // 10 Goats:
        $goatsData = [
            ['tag' => 'PK-GT-001', 'name' => 'Heera (Beetal Champion)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 2, 'weight' => 54.0, 'notes' => '3.2L daily, twin born, robust frame.'],
            ['tag' => 'PK-GT-002', 'name' => 'Moti (Beetal Spotted)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 1, 'weight' => 48.0, 'notes' => 'First lactation, 2.7L daily.'],
            ['tag' => 'PK-GT-003', 'name' => 'Noori (Kamori Long-eared)', 'breed_id' => $breedKamori->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 2, 'weight' => 52.0, 'notes' => 'Exceptional pedigree, 3.4L daily, high milk fat.'],
            ['tag' => 'PK-GT-004', 'name' => 'Kajal (Kamori Black)', 'breed_id' => $breedKamori->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 1, 'weight' => 46.0, 'notes' => 'Good appetite, 2.8L daily.'],
            ['tag' => 'PK-GT-005', 'name' => 'Shehzadi (DDP Cross)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatDry->id, 'struct' => $structPenG2->id, 'status' => 'pregnant', 'stage' => 'pregnant_doeling', 'parity' => 3, 'weight' => 58.0, 'notes' => 'Confirmed pregnant (twins) via ultrasound. Kidding expected in 14 days.'],
            ['tag' => 'PK-GT-006', 'name' => 'Chandni (Beetal White)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 1, 'weight' => 47.0, 'notes' => 'Consistent morning and evening milker.'],
            ['tag' => 'PK-GT-007', 'name' => 'Pari (Saanen Dairy)', 'breed_id' => $breedSaanen->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 2, 'weight' => 61.0, 'notes' => 'High volume goat (3.8L daily), gentle milker.'],
            ['tag' => 'PK-GT-008', 'name' => 'Sona (Beetal Brown)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatMilking->id, 'struct' => $structPenG1->id, 'status' => 'lactating', 'stage' => 'lactating_doe', 'parity' => 2, 'weight' => 53.0, 'notes' => 'Good teat conformation.'],
            ['tag' => 'PK-GT-009', 'name' => 'Laila (Kamori Dairy)', 'breed_id' => $breedKamori->id, 'pen' => $penGoatDry->id, 'struct' => $structPenG2->id, 'status' => 'dry', 'stage' => 'dry_doe', 'parity' => 3, 'weight' => 55.0, 'notes' => 'Dry resting period.'],
            ['tag' => 'PK-GT-010', 'name' => 'Resham (Beetal Maiden)', 'breed_id' => $breedBeetal->id, 'pen' => $penGoatDry->id, 'struct' => $structPenG2->id, 'status' => 'active', 'stage' => 'doeling', 'parity' => 0, 'weight' => 39.0, 'notes' => 'Replacement doe, ready for first breeding.'],
        ];

        $goats = [];
        foreach ($goatsData as $idx => $item) {
            $num = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
            $eid = "982000002{$num}";
            $qr = "FMS-GT-{$num}-QR";

            $goat = Animal::create([
                'organization_id' => $org->id,
                'farm_id' => $farm->id,
                'species_id' => $goatSpecies->id,
                'breed_id' => $item['breed_id'],
                'pen_id' => $item['pen'],
                'structure_id' => $item['struct'],
                'zone_id' => $zoneCompound->id,
                'tag_number' => $item['tag'],
                'electronic_id' => $eid,
                'qr_code_identifier' => $qr,
                'name' => $item['name'],
                'sex' => 'female',
                'birth_date' => Carbon::now()->subMonths(rand(18, 36)),
                'birth_weight_kg' => 3.5,
                'current_weight_kg' => $item['weight'],
                'acquisition_type' => 'purchased',
                'acquisition_date' => Carbon::now()->subMonths(rand(6, 12)),
                'status' => $item['status'],
                'lifecycle_stage' => $item['stage'],
                'parity' => $item['parity'],
                'notes' => $item['notes'],
                'created_by' => $owner->id,
            ]);

            // Identifiers
            AnimalIdentifier::create([
                'animal_id' => $goat->id,
                'id_type' => 'rfid_iso11784',
                'id_value' => $eid,
                'tag_color' => 'blue',
                'tag_placement' => 'left_ear',
                'is_primary' => true,
                'applied_date' => Carbon::now()->subMonths(6),
            ]);

            AnimalIdentifier::create([
                'animal_id' => $goat->id,
                'id_type' => 'visual_ear_tag',
                'id_value' => $item['tag'],
                'tag_color' => 'blue',
                'tag_placement' => 'right_ear',
                'is_primary' => false,
                'applied_date' => Carbon::now()->subMonths(6),
            ]);

            // ADG Weight
            $prevGoatWeight = $item['weight'] - 2.5;
            $adgCalculator->recordWeight(
                animal: $goat,
                weightKg: $prevGoatWeight,
                recordedAt: Carbon::now()->subDays(30),
                weighingMethod: 'scale',
                recordedByUserId: $worker->id
            );
            $adgCalculator->recordWeight(
                animal: $goat,
                weightKg: $item['weight'],
                recordedAt: Carbon::now()->subDays(15),
                weighingMethod: 'scale',
                recordedByUserId: $worker->id
            );

            // BCS Record
            AnimalBcsRecord::create([
                'animal_id' => $goat->id,
                'bcs_score' => 3.25,
                'locomotion_score' => 1,
                'rumen_fill_score' => 4,
                'cleanliness_score' => 1,
                'assessed_at' => Carbon::now()->subDays(15),
                'assessed_by' => $vet->id,
            ]);

            // Group assignment
            if ($item['status'] === 'lactating') {
                AnimalGroupMembership::create([
                    'group_id' => $groupMilkingGoats->id,
                    'animal_id' => $goat->id,
                    'joined_at' => Carbon::now()->subMonths(2),
                    'is_current' => true,
                ]);
            }

            WeightRecord::create([
                'animal_id' => $goat->id,
                'weight_kg' => $item['weight'],
                'recorded_at' => Carbon::now()->subDays(15),
                'recorded_by_name' => $worker->name,
                'body_condition_score' => 3.2,
            ]);

            $goats[] = $goat;
        }

        // 11. Active Health Case & Treatment on COW-005 (Demonstrating Withdrawal Enforcement)
        $sickCow = $cows[4]; // PK-COW-005 (Sundri)
        $healthCase = HealthCase::create([
            'farm_id' => $farm->id,
            'animal_id' => $sickCow->id,
            'case_number' => 'HC-2026-0041',
            'symptom_observed_at' => Carbon::now()->subDays(1),
            'diagnosis' => 'Subclinical Mastitis (Right Hind Quarter)',
            'symptoms_description' => 'Slight swelling, California Mastitis Test (CMT) positive (2+), elevated somatic cell count.',
            'subjective_notes' => 'Milker reported mild udder hardness and quarter sensitivity during morning teat dip.',
            'objective_temp_c' => 39.2,
            'objective_heart_rate' => 74,
            'objective_respiration_rate' => 26,
            'objective_rumen_motility_per_2min' => 3,
            'assessment_notes' => 'Localised subclinical mastitis with CMT 2+. Normal appetite, mild pyrexia.',
            'plan_notes' => 'Administer Amoxicillin 15% IM daily for 3 days. Frequent hand stripping. Monitor withdrawal until clearance.',
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
            'veterinarian_license_number' => 'PVMC-78921-A',
            'prescription_number' => 'RX-2026-00912',
            'batch_lot_number' => 'AMX-2026-B81',
            'active_substance_administered_mg' => 3000.00,
            'ddda_units_consumed' => 0.444,
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

        // 12. Breeding & Pregnancy records
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

        // 13. Bulk Milk Cooling Tanks & Milk Recording
        $bulkTank1 = BulkTank::create([
            'farm_id' => $farm->id,
            'tank_code' => 'TANK-01',
            'model_name' => 'Mueller O-Series 5000L DX Chiller',
            'capacity_liters' => 5000.00,
            'current_volume_liters' => 0.00,
            'target_temperature_c' => 3.8,
            'current_temperature_c' => 3.6,
            'cooling_status' => 'cooling',
            'agitator_status' => 'intermittent',
            'last_cip_cleaned_at' => Carbon::now()->subDays(1),
            'last_cip_cleaned_by' => $worker->id,
            'is_sanitized' => true,
            'status' => 'operational',
        ]);

        $bulkTank2 = BulkTank::create([
            'farm_id' => $farm->id,
            'tank_code' => 'TANK-02',
            'model_name' => 'DeLaval DXCE 1500L Small Ruminant Tank',
            'capacity_liters' => 1500.00,
            'current_volume_liters' => 0.00,
            'target_temperature_c' => 4.0,
            'current_temperature_c' => 3.9,
            'cooling_status' => 'idle',
            'agitator_status' => 'idle',
            'last_cip_cleaned_at' => Carbon::now()->subDays(2),
            'last_cip_cleaned_by' => $worker->id,
            'is_sanitized' => true,
            'status' => 'operational',
        ]);

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
                    'bulk_tank_id' => $bulkTank1->id,
                    'session_date' => $recordDate,
                    'shift' => $shift,
                    'total_yield_liters' => 0,
                    'bulk_tank_temperature_c' => 3.8,
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
                $bulkTank1->increment('current_volume_liters', $sessionTotal);
            }
        }

        // Milk Dispatch (Cold chain tanker to processor)
        MilkDispatch::create([
            'farm_id' => $farm->id,
            'bulk_tank_id' => $bulkTank1->id,
            'dispatch_number' => 'DISP-2026-001',
            'buyer_name' => 'Engro Foods (Olpers Dairy Processing Plant)',
            'driver_name' => 'Muhammad Rafiq',
            'driver_phone' => '+92-301-8849201',
            'tanker_plate_number' => 'LES-8492',
            'seal_number' => 'SEAL-ENG-99120',
            'dispatched_volume_liters' => 150.00,
            'temperature_c' => 3.6,
            'composite_fat_percentage' => 4.10,
            'composite_snf_percentage' => 8.85,
            'composite_scc' => 140000,
            'unit_price_pkr' => 175.00,
            'total_price_pkr' => 150.00 * 175.00,
            'dispatched_at' => Carbon::now()->subHours(4),
            'authorized_by' => $owner->id,
            'status' => 'in_transit',
            'notes' => 'Cold-chain dispatch verified at 3.6C. Dual tamper-evident bolt seal applied.',
        ]);
        $bulkTank1->decrement('current_volume_liters', 150.00);

        // Milk Collection Center (MCC) & Cooperative 2D Pricing Grid
        $mcc = MilkCollectionCenter::create([
            'organization_id' => $org->id,
            'center_code' => 'MCC-KASUR-01',
            'name' => 'Kasur Central Dairy Chilling Hub',
            'location' => 'Changa Manga Road, Kasur District',
            'latitude' => 31.1158000,
            'longitude' => 74.4501000,
            'chilling_capacity_liters' => 8000.00,
            'current_volume_liters' => 1240.00,
            'route_code' => 'ROUTE-KASUR-NORTH',
            'status' => 'active',
        ]);

        $cowRateChart = MilkRateChart::create([
            'organization_id' => $org->id,
            'name' => 'Standard Cow Milk Rate Chart 2026',
            'species_type' => 'cow',
            'base_price_per_liter' => 140.00,
            'standard_fat_percentage' => 3.50,
            'standard_snf_percentage' => 8.50,
            'fat_rate_per_unit' => 12.00,
            'snf_rate_per_unit' => 8.00,
            'min_fat_acceptance' => 3.00,
            'min_snf_acceptance' => 8.00,
            'premium_incentive_percent' => 2.50,
            'effective_from' => Carbon::now()->subMonths(3)->toDateString(),
            'effective_until' => Carbon::now()->addYear()->toDateString(),
            'is_active' => true,
        ]);

        $buffaloRateChart = MilkRateChart::create([
            'organization_id' => $org->id,
            'name' => 'Premium Buffalo Milk Rate Chart 2026',
            'species_type' => 'buffalo',
            'base_price_per_liter' => 190.00,
            'standard_fat_percentage' => 6.00,
            'standard_snf_percentage' => 9.00,
            'fat_rate_per_unit' => 15.00,
            'snf_rate_per_unit' => 10.00,
            'min_fat_acceptance' => 5.00,
            'min_snf_acceptance' => 8.50,
            'premium_incentive_percent' => 3.00,
            'effective_from' => Carbon::now()->subMonths(3)->toDateString(),
            'effective_until' => Carbon::now()->addYear()->toDateString(),
            'is_active' => true,
        ]);

        $supplier1 = FarmerSupplier::create([
            'organization_id' => $org->id,
            'collection_center_id' => $mcc->id,
            'supplier_code' => 'SUP-001',
            'name' => 'Baba Rehmat Din',
            'phone' => '+92-300-1122334',
            'cnic_or_national_id' => '35102-1234567-1',
            'village_address' => 'Chak 14-RB, Tehsil Chunian',
            'cattle_count' => 6,
            'buffalo_count' => 4,
            'goat_count' => 2,
            'payout_channel' => 'jazzcash',
            'payout_account_number' => '03001122334',
            'payout_account_title' => 'Rehmat Din',
            'is_active' => true,
        ]);

        $supplier2 = FarmerSupplier::create([
            'organization_id' => $org->id,
            'collection_center_id' => $mcc->id,
            'supplier_code' => 'SUP-002',
            'name' => 'Chaudhry Akhtar Ali',
            'phone' => '+92-301-5566778',
            'cnic_or_national_id' => '35102-7654321-3',
            'village_address' => 'Kot Radha Kishan Road',
            'cattle_count' => 12,
            'buffalo_count' => 8,
            'goat_count' => 0,
            'payout_channel' => 'bank_transfer',
            'payout_account_number' => 'PK36HABB00012345678901',
            'payout_account_title' => 'Akhtar Ali Dairy',
            'is_active' => true,
        ]);

        // Sample accepted intake using 2D pricing engine
        $pricingCalculator = new TwoDimensionalRateChartPricingCalculator;
        $clr = 29.0;
        $fat = 4.2;
        $snf = $pricingCalculator->calculateSnfFromLactometer($clr, $fat, 'cow');
        $pricing = $pricingCalculator->calculateIntakePrice($cowRateChart, 45.0, $fat, $snf, [
            'alcohol_test_result' => 'negative',
        ]);

        MilkCollectionIntake::create([
            'collection_center_id' => $mcc->id,
            'farmer_supplier_id' => $supplier1->id,
            'rate_chart_id' => $cowRateChart->id,
            'intake_number' => 'INTAKE-2026-0001',
            'collection_date' => Carbon::now()->toDateString(),
            'shift' => 'morning',
            'species_type' => 'cow',
            'gross_volume_liters' => 45.00,
            'lactometer_reading' => $clr,
            'fat_percentage' => $fat,
            'snf_percentage' => $snf,
            'calculated_price_per_liter' => $pricing['price_per_liter'],
            'gross_amount' => $pricing['gross_amount'],
            'deductions_amount' => $pricing['deductions_amount'],
            'net_payable_amount' => $pricing['net_payable_amount'],
            'payment_status' => 'paid',
            'paid_at' => Carbon::now()->subHours(2),
            'paid_via_reference' => 'JC-TXN-984210',
            'alcohol_test_result' => 'negative',
            'adulteration_starch' => false,
            'adulteration_urea' => false,
            'adulteration_detergent' => false,
            'adulteration_formalin' => false,
            'adulteration_hydrogen_peroxide' => false,
            'added_water_percentage' => 0.00,
            'quality_accepted' => true,
            'operator_id' => $worker->id,
        ]);

        // 14. Feed Daily Consumption
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

        // Phase 3: Feed Formulations (NRC 2001 Ration Templates)
        $rationOptimizer = new RationFormulationOptimizerService;

        $cowIngredients = [
            ['feed_item_id' => $silage->id, 'inclusion_kg_as_fed' => 28.0],
            ['feed_item_id' => $alfalfaHay->id, 'inclusion_kg_as_fed' => 4.0],
            ['feed_item_id' => $concentrate->id, 'inclusion_kg_as_fed' => 8.0],
            ['feed_item_id' => $cottonseedCake->id, 'inclusion_kg_as_fed' => 2.5],
        ];
        $cowEval = $rationOptimizer->evaluateRationNutrients($cowIngredients, 22.0);

        $cowFormulation = FeedFormulation::create([
            'farm_id' => $farm->id,
            'name' => 'High-Yield Lactating Cow TMR (NRC 2001)',
            'code' => 'TMR-COW-HIGH',
            'species_type' => 'cattle',
            'target_stage' => 'lactating_high',
            'target_dmi_kg' => 22.50,
            'calculated_cp_percent' => $cowEval['crude_protein_percent'],
            'calculated_nel_mcal' => $cowEval['nel_mcal_total'],
            'calculated_cost_per_head_day' => $cowEval['total_cost_per_head_day'],
            'ingredients' => $cowIngredients,
            'is_active' => true,
            'notes' => 'Balanced for 25L daily yield at 3.8% fat and 3.2% protein.',
        ]);

        $goatIngredients = [
            ['feed_item_id' => $goatFeed->id, 'inclusion_kg_as_fed' => 1.5],
            ['feed_item_id' => $alfalfaHay->id, 'inclusion_kg_as_fed' => 1.0],
        ];
        $goatEval = $rationOptimizer->evaluateRationNutrients($goatIngredients, 3.5);

        $goatFormulation = FeedFormulation::create([
            'farm_id' => $farm->id,
            'name' => 'Dairy Doe Lactation Ration',
            'code' => 'RAT-GOAT-LAC',
            'species_type' => 'goat',
            'target_stage' => 'lactating',
            'target_dmi_kg' => 2.30,
            'calculated_cp_percent' => $goatEval['crude_protein_percent'],
            'calculated_nel_mcal' => $goatEval['nel_mcal_total'],
            'calculated_cost_per_head_day' => $goatEval['total_cost_per_head_day'],
            'ingredients' => $goatIngredients,
            'is_active' => true,
            'notes' => 'Formulated for 3L+ milking does.',
        ]);

        // Silage Bunker & Clamp Management
        SilageBunker::create([
            'farm_id' => $farm->id,
            'bunker_code' => 'BUNKER-01',
            'name' => 'Main Bunker Pit #1 (Pioneer Corn)',
            'crop_type' => 'corn_maize',
            'initial_tonnage' => 450.00,
            'remaining_tonnage' => 385.00,
            'face_temperature_c' => 21.8,
            'ph_level' => 3.9,
            'compaction_density_kg_m3' => 660.0,
            'fermentation_score' => 'excellent',
            'status' => 'open_feeding',
        ]);

        // TMR Mixer Wagon Batch Production
        TmrBatch::create([
            'farm_id' => $farm->id,
            'feed_formulation_id' => $cowFormulation->id,
            'pen_id' => $penCowMilking->id,
            'batch_number' => 'TMR-20261001-A109',
            'mixer_wagon_id' => 'Keenan MechFiber 360',
            'planned_weight_kg' => 170.00,
            'actual_weight_kg' => 168.50,
            'deviation_percent' => -0.88,
            'mixing_duration_minutes' => 14,
            'status' => 'dispatched_to_bunk',
            'operator_id' => $worker->id,
            'batch_timestamp' => Carbon::now()->subHours(6),
        ]);

        // Feed Bunk Score Assessment
        FeedBunkScore::create([
            'farm_id' => $farm->id,
            'pen_id' => $penCowMilking->id,
            'assessed_at' => Carbon::now()->subHours(1),
            'score' => 1, // Scattered crumbs, ideal feed intake
            'refusal_estimated_kg' => 4.50,
            'adjustment_action' => 'maintain',
            'assessed_by' => $worker->id,
            'notes' => 'Clean bunk lines. Animals chewing cud normally.',
        ]);

        // Liquid Nitrogen Semen Straw Inventory
        $semenHf = SemenStrawInventory::create([
            'farm_id' => $farm->id,
            'straw_code' => 'SEMEN-HF-9901',
            'sire_name' => 'AltaKevlow Elite 511HO08',
            'sire_breed' => 'Holstein Friesian',
            'naab_code' => '011HO12345',
            'semen_type' => 'sexed_female',
            'canister_location' => 'MVE Tank #1 - Canister 2',
            'cane_number' => 'Cane 01',
            'straws_in_stock' => 38,
            'unit_cost_pkr' => 4500.00,
        ]);

        $semenSw = SemenStrawInventory::create([
            'farm_id' => $farm->id,
            'straw_code' => 'SEMEN-SW-8812',
            'sire_name' => 'Rustam-II Proven Bull (Semex Sahiwal)',
            'sire_breed' => 'Sahiwal',
            'naab_code' => '055SW09812',
            'semen_type' => 'conventional',
            'canister_location' => 'MVE Tank #1 - Canister 3',
            'cane_number' => 'Cane 04',
            'straws_in_stock' => 50,
            'unit_cost_pkr' => 1800.00,
        ]);

        // Biosecurity Audit
        BiosecurityAudit::create([
            'farm_id' => $farm->id,
            'audit_date' => Carbon::now()->subDays(5)->toDateString(),
            'auditor_name' => 'Dr. Khalid Hussain (Provincial Livestock Dept)',
            'visitor_log_compliance_score' => 95,
            'footbath_disinfection_score' => 92,
            'quarantine_compliance_score' => 100,
            'carcass_disposal_compliance_score' => 100,
            'overall_risk_rating' => 'low_risk',
            'corrective_actions' => 'Replenish Virkon-S footbath chemical every 48 hours at milking parlor gate.',
        ]);

        // Historical Birth & Postpartum Examination
        $damCow = $cows[0]; // PK-COW-001 (Rani)
        $historicalBirth = Birth::create([
            'farm_id' => $farm->id,
            'dam_id' => $damCow->id,
            'sire_id' => null,
            'calving_datetime' => Carbon::now()->subMonths(3),
            'calving_ease' => 'easy_unassisted',
            'birth_weight_kg' => 36.50,
            'offspring_sex' => 'female',
            'offspring_count' => 1,
            'live_count' => 1,
            'stillborn_count' => 0,
            'colostrum_fed' => true,
            'colostrum_liters' => 4.0,
            'attendant_name' => $vet->name,
            'notes' => 'Normal calving. Heifer calf vigorous and fed colostrum within 1 hour.',
        ]);

        PostpartumCheck::create([
            'farm_id' => $farm->id,
            'animal_id' => $damCow->id,
            'birth_id' => $historicalBirth->id,
            'check_date' => Carbon::now()->subMonths(3)->addDays(10)->toDateString(),
            'days_in_milk' => 10,
            'rectal_temperature_c' => 38.6,
            'lochia_score' => 0,
            'ketosis_test_bhb_mmol_l' => 0.85,
            'uterine_involution_status' => 'normal_involution',
            'checked_by' => $vet->name,
            'clinical_notes' => 'Normal uterine involution. No metritis or ketosis detected.',
        ]);

        // 15. Climate & THI Real-time Reading
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

        // 16. Suppliers & Finance
        $feedSupplier = Supplier::create([
            'farm_id' => $farm->id,
            'name' => 'Punjab Corn Silage & Agrico',
            'contact_person' => 'Malik Shakeel',
            'phone' => '+92-321-4567890',
            'category' => 'feed',
            'address' => 'Depalpur Bypass, Okara',
        ]);

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

        // 17. Operational Tasks & Reminders
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

        // 18. Audit Log Initialization
        AuditLog::create([
            'organization_id' => $org->id,
            'farm_id' => $farm->id,
            'user_id' => $owner->id,
            'action' => 'herd_master_onboarding',
            'auditable_type' => Farm::class,
            'auditable_id' => $farm->id,
            'new_values' => [
                'total_livestock_count' => 15,
                'cattle_count' => 5,
                'goat_count' => 10,
                'structures_configured' => 7,
                'zones_configured' => 4,
                'rbac_roles_initialized' => 4,
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'FMS-Pilot-Seeder/Phase1',
            'created_at' => Carbon::now()->subMonths(6),
        ]);

        // 19. Phase 4: Meat, Pasture, Inventory & Direct Sales Seeding
        // 19.1 Pasture Plots & Grazing
        $pastureAlfalfa = PasturePlot::create([
            'farm_id' => $farm->id,
            'name' => 'South Paddock 1 - Pioneer Alfalfa',
            'code' => 'PAD-ALF-01',
            'area_hectares' => 3.20,
            'forage_type' => 'alfalfa',
            'soil_ph' => 6.8,
            'target_rest_days' => 28,
            'current_biomass_kg_dm_per_ha' => 2850.00,
            'status' => 'resting',
            'last_grazed_at' => Carbon::now()->subDays(14),
        ]);

        $pastureRhodes = PasturePlot::create([
            'farm_id' => $farm->id,
            'name' => 'North Paddock 2 - Rhodes Grass & Clover',
            'code' => 'PAD-RHO-02',
            'area_hectares' => 4.50,
            'forage_type' => 'rhodes_grass',
            'soil_ph' => 7.1,
            'target_rest_days' => 25,
            'current_biomass_kg_dm_per_ha' => 3200.00,
            'status' => 'grazing',
            'last_grazed_at' => Carbon::now()->subDays(2),
        ]);

        $goatGroup = AnimalGroup::first();

        GrazingLog::create([
            'farm_id' => $farm->id,
            'pasture_plot_id' => $pastureRhodes->id,
            'animal_group_id' => $goatGroup?->id,
            'entry_date' => Carbon::now()->subDays(2)->toDateString(),
            'stocking_density_heads' => 10,
            'livestock_units_per_ha' => 2.22,
            'pre_graze_height_cm' => 28.5,
            'dry_matter_utilized_kg_ha' => 0.00,
            'notes' => 'Early spring rotational grazing of goat milking group',
        ]);

        // 19.2 Beef Feedlot & Performance
        $feedlotAnimal = Animal::where('farm_id', $farm->id)->first();
        if ($feedlotAnimal) {
            FeedlotRecord::create([
                'farm_id' => $farm->id,
                'animal_id' => $feedlotAnimal->id,
                'pen_id' => $feedlotAnimal->pen_id,
                'intake_date' => Carbon::now()->subDays(60)->toDateString(),
                'intake_weight_kg' => 380.00,
                'current_weight_kg' => 462.50,
                'target_slaughter_weight_kg' => 550.00,
                'days_on_feed' => 60,
                'average_daily_gain_kg' => 1.375,
                'total_gain_kg' => 82.50,
                'total_feed_consumed_kg_dm' => 536.25,
                'feed_conversion_ratio' => 6.50,
                'daily_ration_cost' => 420.00,
                'cost_per_kg_gain' => 305.45,
                'status' => 'active',
                'notes' => 'High-energy corn silage and maize finishing ration',
            ]);

            SpecializedSpeciesAttribute::create([
                'animal_id' => $feedlotAnimal->id,
                'species_type' => 'cattle',
                'hump_condition_score' => 4.2,
                'draft_work_type' => 'meat',
                'racing_eligibility_status' => false,
                'veterinary_passport_number' => 'PK-VET-PASS-9902',
                'microchip_transponder_rfid' => '982000412891001',
            ]);
        }

        // 19.3 Fleece Records (Fiber)
        $goatAnimal = Animal::where('farm_id', $farm->id)->where('species_id', $goatSpecies->id)->first();
        if ($goatAnimal) {
            FleeceRecord::create([
                'farm_id' => $farm->id,
                'animal_id' => $goatAnimal->id,
                'shearing_date' => Carbon::now()->subMonths(2)->toDateString(),
                'fleece_type' => 'cashmere',
                'grease_fleece_weight_kg' => 1.85,
                'clean_fleece_weight_kg' => 1.20,
                'clean_yield_percentage' => 64.86,
                'micron_grade' => 16.8,
                'quality_tier' => 'ultrafine',
                'staple_length_mm' => 55.0,
                'shearer_name' => 'Ustad Munir (Master Shearer)',
                'notes' => 'Premium ultrafine spring undercoat combing',
            ]);
        }

        // 19.4 Multi-Warehouse Inventory & Equipment Assets
        $whFeed = Warehouse::create([
            'farm_id' => $farm->id,
            'name' => 'Main Feed, Forage & Grain Warehouse',
            'code' => 'WH-FEED-01',
            'type' => 'feed_store',
            'temperature_controlled' => false,
            'is_active' => true,
        ]);

        $whPharma = Warehouse::create([
            'farm_id' => $farm->id,
            'name' => 'Veterinary Cold-Chain Pharmacy',
            'code' => 'WH-PHARMA-01',
            'type' => 'cold_pharmacy',
            'temperature_controlled' => true,
            'target_temp_c' => 4.0,
            'is_active' => true,
        ]);

        $skuFeedWanda = InventoryItem::create([
            'farm_id' => $farm->id,
            'warehouse_id' => $whFeed->id,
            'category' => 'feed',
            'sku' => 'SKU-FEED-WANDA-18CP',
            'name' => 'High-Performance 18% CP Dairy Wanda (50kg Bag)',
            'unit_of_measure' => 'bag',
            'current_stock_quantity' => 140.00,
            'reorder_level_quantity' => 30.00,
            'safety_stock_quantity' => 15.00,
            'unit_cost' => 3450.00,
            'is_active' => true,
        ]);

        $skuCeftiofur = InventoryItem::create([
            'farm_id' => $farm->id,
            'warehouse_id' => $whPharma->id,
            'category' => 'medicine',
            'sku' => 'SKU-MED-CEFT-100ML',
            'name' => 'Ceftiofur Sodium Sterile Powder 100mL',
            'unit_of_measure' => 'vial',
            'current_stock_quantity' => 24.00,
            'reorder_level_quantity' => 8.00,
            'safety_stock_quantity' => 4.00,
            'unit_cost' => 1850.00,
            'is_active' => true,
        ]);

        InventoryTransaction::create([
            'farm_id' => $farm->id,
            'inventory_item_id' => $skuFeedWanda->id,
            'warehouse_id' => $whFeed->id,
            'transaction_type' => 'goods_receipt',
            'quantity' => 100.00,
            'unit_cost' => 3450.00,
            'total_cost' => 345000.00,
            'batch_number' => 'LOT-WD-2026-08',
            'expiry_date' => Carbon::now()->addMonths(6)->toDateString(),
            'notes' => 'Bulk seasonal purchase order delivery from Punjab Feed Mills',
        ]);

        $tractor = FarmAsset::create([
            'farm_id' => $farm->id,
            'name' => 'Massey Ferguson 385 4WD Tractor',
            'asset_code' => 'ASSET-TRAC-01',
            'category' => 'tractor',
            'make' => 'Millat Tractors / MF',
            'model' => 'MF-385',
            'serial_number' => 'MF385-4WD-2024-8812',
            'purchase_date' => Carbon::now()->subYears(2)->toDateString(),
            'purchase_cost' => 3200000.00,
            'meter_type' => 'hours',
            'current_meter_reading' => 1480.5,
            'status' => 'operational',
        ]);

        MaintenanceLog::create([
            'farm_asset_id' => $tractor->id,
            'maintenance_type' => 'preventative',
            'service_date' => Carbon::now()->subDays(15)->toDateString(),
            'technician_name' => 'Zubair Diesel Works',
            'meter_reading' => 1450.0,
            'downtime_hours' => 3.50,
            'parts_cost' => 18500.00,
            'labor_cost' => 6000.00,
            'total_cost' => 24500.00,
            'next_service_due_date' => Carbon::now()->addMonths(3)->toDateString(),
            'next_service_due_meter' => 1700.0,
            'notes' => 'Engine oil replacement, fuel filter replacement, and hydraulic pump pressure check',
        ]);

        // 19.5 Direct Milk Distribution & Household Subscriptions
        $customer1 = Customer::create([
            'organization_id' => $org->id,
            'farm_id' => $farm->id,
            'customer_type' => 'household_subscription',
            'name' => 'Haji Abdul Rehman',
            'phone' => '+92-300-4491823',
            'email' => 'abdul.rehman@lahore-residence.pk',
            'address' => 'House 142, Street 7, Sector Y, DHA Phase 5',
            'city' => 'Lahore',
            'latitude' => 31.4682000,
            'longitude' => 74.3912000,
            'wallet_balance' => 4500.00,
            'status' => 'active',
        ]);

        $customer2 = Customer::create([
            'organization_id' => $org->id,
            'farm_id' => $farm->id,
            'customer_type' => 'retail_store',
            'name' => 'Lahore Artisan Farm Shop & Cafe',
            'phone' => '+92-321-9988112',
            'email' => 'procurement@artisanfarm.pk',
            'address' => 'Plaza 18, Commercial Broadway, Gulberg III',
            'city' => 'Lahore',
            'latitude' => 31.5120000,
            'longitude' => 74.3540000,
            'wallet_balance' => 12000.00,
            'status' => 'active',
        ]);

        $sub1 = CustomerSubscription::create([
            'customer_id' => $customer1->id,
            'farm_id' => $farm->id,
            'product_type' => 'raw_cow_milk',
            'daily_quantity_liters' => 3.00,
            'unit_price_per_liter' => 230.00,
            'frequency' => 'daily',
            'start_date' => Carbon::now()->subMonths(1)->toDateString(),
            'is_paused' => false,
            'status' => 'active',
        ]);

        $sub2 = CustomerSubscription::create([
            'customer_id' => $customer2->id,
            'farm_id' => $farm->id,
            'product_type' => 'raw_goat_milk',
            'daily_quantity_liters' => 5.00,
            'unit_price_per_liter' => 340.00,
            'frequency' => 'daily',
            'start_date' => Carbon::now()->subMonths(1)->toDateString(),
            'is_paused' => false,
            'status' => 'active',
        ]);

        CustomerWalletTransaction::create([
            'customer_id' => $customer1->id,
            'transaction_type' => 'prepaid_topup',
            'amount' => 5000.00,
            'opening_balance' => 0.00,
            'closing_balance' => 5000.00,
            'reference_id' => 'BANK-JAZZCASH-99182',
            'notes' => 'Prepaid online milk subscription recharge',
        ]);

        $run = DeliveryRun::create([
            'farm_id' => $farm->id,
            'run_date' => Carbon::now()->toDateString(),
            'route_name' => 'DHA Phase 5 & Gulberg Morning Run',
            'driver_name' => 'Tariq Mehmood',
            'vehicle_plate_number' => 'LEA-8819',
            'vehicle_departure_temp_c' => 3.6,
            'total_liters_planned' => 8.00,
            'total_liters_delivered' => 0.00,
            'status' => 'in_progress',
        ]);

        DeliveryRunStop::create([
            'delivery_run_id' => $run->id,
            'customer_id' => $customer1->id,
            'customer_subscription_id' => $sub1->id,
            'stop_sequence' => 1,
            'planned_quantity_liters' => 3.00,
            'delivered_quantity_liters' => 0.00,
            'unit_price' => 230.00,
            'total_amount' => 690.00,
            'empty_bottles_returned' => 0,
            'proof_of_delivery_type' => 'otp',
            'proof_of_delivery_token' => '4821',
            'status' => 'pending',
            'notes' => 'Leave inside insulated cooler box at main gate',
        ]);

        DeliveryRunStop::create([
            'delivery_run_id' => $run->id,
            'customer_id' => $customer2->id,
            'customer_subscription_id' => $sub2->id,
            'stop_sequence' => 2,
            'planned_quantity_liters' => 5.00,
            'delivered_quantity_liters' => 0.00,
            'unit_price' => 340.00,
            'total_amount' => 1700.00,
            'empty_bottles_returned' => 5,
            'proof_of_delivery_type' => 'digital_signature',
            'proof_of_delivery_token' => 'SIG-ARTISAN-CAFE',
            'status' => 'pending',
            'notes' => 'Deliver to pastry kitchen chef',
        ]);

        // 20. Phase 5: Financial Accounting, Compliance Packs, Traceability & Sync
        // 20.1 Chart of Accounts (COA)
        $coaCash = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '1010',
            'name' => 'Cash on Hand & Farm Clearing Account',
            'account_type' => 'asset',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaBank = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '1020',
            'name' => 'Habib Bank Ltd (HBL) Agribusiness Operating Account',
            'account_type' => 'asset',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaBiologicalAssets = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '1510',
            'name' => 'Biological Assets - Dairy Cattle & Goats Herd',
            'account_type' => 'asset',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaAccountsPayable = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '2010',
            'name' => 'Trade Accounts Payable (Feed & Pharma Suppliers)',
            'account_type' => 'liability',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaEquity = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '3010',
            'name' => 'Owner Contributed Capital',
            'account_type' => 'equity',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaMilkRevenue = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '4010',
            'name' => 'Commercial Milk & Value-Added Dairy Sales Revenue',
            'account_type' => 'revenue',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaBioGain = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '4510',
            'name' => 'IAS-41 Fair Value Biological Asset Valuation Gain',
            'account_type' => 'revenue',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaFeedExpense = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '5010',
            'name' => 'Feed, Forage, Wanda & TMR Rations Expense',
            'account_type' => 'direct_expense',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        $coaVetExpense = ChartOfAccount::create([
            'organization_id' => $org->id,
            'account_code' => '5020',
            'name' => 'Veterinary Healthcare & Breeding AI Expense',
            'account_type' => 'direct_expense',
            'currency' => 'PKR',
            'is_active' => true,
        ]);

        // 20.2 Initial General Ledger Journal Vouchers
        GeneralLedgerEntry::create([
            'organization_id' => $org->id,
            'farm_id' => $farm->id,
            'entry_number' => 'JV-2026-OP-01',
            'entry_date' => Carbon::now()->subMonths(6)->toDateString(),
            'debit_account_id' => $coaBank->id,
            'credit_account_id' => $coaEquity->id,
            'amount' => 15000000.00,
            'currency' => 'PKR',
            'reference_type' => 'capital_injection',
            'description' => 'Initial equity capital investment for pilot farm establishment',
            'created_by' => $owner->id,
        ]);

        GeneralLedgerEntry::create([
            'organization_id' => $org->id,
            'farm_id' => $farm->id,
            'entry_number' => 'JV-2026-FEED-01',
            'entry_date' => Carbon::now()->subMonths(1)->toDateString(),
            'debit_account_id' => $coaFeedExpense->id,
            'credit_account_id' => $coaAccountsPayable->id,
            'amount' => 450000.00,
            'currency' => 'PKR',
            'reference_type' => 'feed_purchase',
            'description' => 'Procurement of 100 bags 18% CP dairy wanda and silage bales',
            'created_by' => $worker->id,
        ]);

        // 20.3 Cost Allocation Rules
        CostAllocationRule::create([
            'farm_id' => $farm->id,
            'name' => 'Farm Labor & Milking Parlor Wages Allocation',
            'cost_category' => 'labor',
            'allocation_basis' => 'milk_volume_ratio',
            'percentage_dairy_cattle' => 70.00,
            'percentage_goats' => 20.00,
            'percentage_feedlot' => 10.00,
        ]);

        CostAllocationRule::create([
            'farm_id' => $farm->id,
            'name' => 'Tubewell & Parlor Electricity/Diesel Allocation',
            'cost_category' => 'electricity_diesel',
            'allocation_basis' => 'headcount_ratio',
            'percentage_dairy_cattle' => 50.00,
            'percentage_goats' => 35.00,
            'percentage_feedlot' => 15.00,
        ]);

        // 20.4 IAS-41 Biological Asset Fair-Value Appraisals
        $firstCow = Animal::where('species_id', $cattleSpecies->id)->first();
        if ($firstCow) {
            BiologicalAssetValuation::create([
                'farm_id' => $farm->id,
                'animal_id' => $firstCow->id,
                'valuation_date' => Carbon::now()->toDateString(),
                'fair_value_amount' => 320000.00,
                'estimated_cost_to_sell' => 16000.00,
                'net_carrying_value' => 304000.00,
                'valuation_method' => 'market_comparison',
                'maturity_stage' => 'mature_lactating',
                'valuer_name' => 'Dr. Khalid Mahmood (Director Livestock Valuation)',
                'notes' => 'High-yielding pure Sahiwal cow in 2nd lactation',
            ]);
        }

        // 20.5 Compliance Packs
        CompliancePack::create([
            'code' => 'pakistan_pfa',
            'name' => 'Punjab Food Authority (PFA) Dairy & Raw Milk Regulations 2024',
            'country_code' => 'PK',
            'regulatory_body' => 'Punjab Food Authority (PFA)',
            'version' => '2024.1',
            'is_enabled' => true,
            'configuration_json' => [
                'min_cow_fat_percent' => 3.5,
                'min_cow_snf_percent' => 8.5,
                'min_buffalo_fat_percent' => 6.0,
                'adulterants_strictly_prohibited' => ['urea', 'formalin', 'detergent', 'starch', 'h2o2'],
                'temp_threshold_cold_chain_c' => 4.0,
            ],
        ]);

        CompliancePack::create([
            'code' => 'gcc_adafsa',
            'name' => 'GCC / Abu Dhabi Agriculture and Food Safety Authority (ADAFSA)',
            'country_code' => 'AE',
            'regulatory_body' => 'ADAFSA',
            'version' => '3.0.0',
            'is_enabled' => false,
            'configuration_json' => [
                'mandatory_ear_tag_iso' => true,
                'thi_heat_stress_audit_required' => true,
            ],
        ]);

        // 20.6 Animal Movement & Transport Permit
        AnimalMovementPermit::create([
            'farm_id' => $farm->id,
            'permit_number' => 'PERMIT-PK-2026-0081',
            'departure_date' => Carbon::now()->toDateString(),
            'movement_purpose' => 'slaughter',
            'origin_premises_id' => $farm->code ?? 'FARM-PILOT-01',
            'destination_premises_name' => 'Punjab Halal Abattoir Lahore',
            'destination_premises_id' => 'ABATTOIR-PHDA-04',
            'destination_address' => 'Shahpur Kanjran Livestock Complex, Multan Road, Lahore',
            'vehicle_plate_number' => 'LES-9921',
            'driver_name' => 'Muhammad Younas',
            'driver_phone' => '+92-301-7788112',
            'animal_ids_json' => [['id' => 1, 'tag' => 'PK-COW-001']],
            'total_heads' => 1,
            'veterinary_health_certificate_no' => 'VET-CERT-LAH-2026-901',
            'status' => 'approved',
            'approved_by' => $owner->id,
            'notes' => 'Transport of beef steer with negative withdrawal verification',
        ]);

        // 20.7 Halal Slaughter Certification
        HalalSlaughterCertification::create([
            'farm_id' => $farm->id,
            'slaughter_record_id' => null,
            'certificate_number' => 'HALAL-PHDA-2026-4410',
            'certification_body' => 'Punjab Halal Development Agency (PHDA)',
            'slaughterer_name' => 'Qari Abdul Ghaffar',
            'slaughterer_credential_id' => 'PHDA-SH-99281',
            'slaughter_method' => 'tazkiyah_non_stun',
            'tasmiyah_recited' => true,
            'trachea_esophagus_jugular_cut_verified' => true,
            'inspector_name' => 'Mufti Muhammad Bilal',
            'verified_at' => Carbon::now()->subDays(5),
        ]);

        // 20.8 Animal Welfare & 5-Freedoms Audit
        AnimalWelfareAssessment::create([
            'farm_id' => $farm->id,
            'audit_date' => Carbon::now()->subDays(10)->toDateString(),
            'auditor_name' => 'Dr. Samina Altaf (Livestock Welfare Officer)',
            'water_access_score' => 5,
            'thermal_comfort_score' => 4,
            'bedding_cleanliness_score' => 5,
            'lameness_prevalence_percent' => 0.00,
            'space_allowance_score' => 5,
            'overall_welfare_grade' => 'excellent',
            'corrective_actions' => 'None required. Excellent pasture shade and elevated goat flooring observed.',
        ]);

        // 20.9 IoT Hardware Device Registries & Telemetry
        $deviceRfid = DeviceRegistry::create([
            'farm_id' => $farm->id,
            'device_identifier' => 'STICK-READER-ALLFLEX-01',
            'device_name' => 'Allflex RS420 Rugged RFID Stick Reader',
            'device_type' => 'rfid_stick_reader',
            'api_key' => 'iot_live_allflex_rs420_secret_key_8891',
            'firmware_version' => 'v2.4.1',
            'ip_address' => '192.168.1.105',
            'battery_percentage' => 92.50,
            'last_heartbeat_at' => Carbon::now()->subMinutes(2),
            'status' => 'online',
        ]);

        DeviceTelemetryLog::create([
            'device_registry_id' => $deviceRfid->id,
            'recorded_at' => Carbon::now()->subMinutes(15),
            'metric_name' => 'rfid_tag_scanned',
            'metric_value' => 1.0,
            'unit_of_measure' => 'iso_id',
            'raw_payload_json' => [
                'tag_scanned' => '982000412891001',
                'antenna_power_dbm' => 27,
                'read_duration_ms' => 45,
            ],
        ]);

        // 20.10 Mobile Offline Sync Client & Revision Log
        SyncClientRegistry::create([
            'user_id' => $worker->id,
            'device_uuid' => 'ANDROID-TABLET-BARN-01',
            'app_version' => '1.0.4',
            'platform' => 'android',
            'last_sync_rev_id' => 1,
            'last_synced_at' => Carbon::now()->subHours(1),
        ]);

        SyncChangeLog::create([
            'organization_id' => $org->id,
            'entity_type' => 'milk_records',
            'entity_id' => '1',
            'action' => 'insert',
            'idempotency_key' => 'SYNC-MILK-2026-10-01-001',
            'delta_payload_json' => [
                'animal_id' => 1,
                'milking_date' => Carbon::now()->toDateString(),
                'yield_liters' => 14.5,
            ],
            'timestamp' => Carbon::now(),
        ]);
    }
}
