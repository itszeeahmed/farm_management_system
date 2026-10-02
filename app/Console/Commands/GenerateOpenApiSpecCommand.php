<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateOpenApiSpecCommand extends Command
{
    protected $signature = 'api:generate-docs';

    protected $description = 'Generate full OpenAPI 3.0.3 JSON specification file for Farm Management System';

    public function handle(): int
    {
        $this->info('Generating OpenAPI 3.0.3 Specification...');

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Farm Management System (v2.0) REST API',
                'description' => 'Complete enterprise-grade Agritech REST API specification adhering to international standards (NRC 2001, OIE, IAS-41, ISO 11784/11785, WHO CIA, PFA). Built with Laravel 13, PostgreSQL 18, and PHP 8.3.',
                'version' => '2.0.0',
                'contact' => [
                    'name' => 'Antigravity Agritech Engineering Team',
                    'email' => 'engineering@farm-system.local',
                ],
                'license' => [
                    'name' => 'Proprietary / Enterprise',
                ],
            ],
            'servers' => [
                [
                    'url' => '/api/v1',
                    'description' => 'API v1 Production / Local Gateway',
                ],
            ],
            'tags' => [
                ['name' => 'System Health', 'description' => 'Diagnostics and infrastructure health monitoring'],
                ['name' => 'Authentication & RBAC', 'description' => 'User authentication, roles, permissions, and multi-farm access'],
                ['name' => 'Multi-Tenant Hierarchy', 'description' => 'Organizations, farms, zones, barns, paddocks, and structures'],
                ['name' => 'Livestock & Pedigree', 'description' => 'Animal master, multi-tag RFID, pedigree lineages, weights & ADG, body condition scoring'],
                ['name' => 'Dairy Production & Quality', 'description' => 'Milking sessions, individual yields, SCC quality grading, antimicrobial withholding safety guard'],
                ['name' => 'Cooperative Collection Centers', 'description' => 'Smallholder intake, 2D fat/SNF pricing grids, adulteration rejection, bulk tanker dispatch'],
                ['name' => 'Feed, Nutrition & Silage', 'description' => 'NRC 2001 ration balancing, TMR mixer wagon batches, Penn State bunk scores, silage bunker clamps'],
                ['name' => 'Veterinary Health & Biosecurity', 'description' => 'Clinical SOAP cases, treatments, WHO antimicrobial usage (DDDA) tracking, biosecurity audits'],
                ['name' => 'Reproduction & Calving', 'description' => 'Insemination, pregnancy checks, cryogenic semen inventory, calving lifecycle engine'],
                ['name' => 'Meat, Feedlot & Fleece', 'description' => 'Feedlot ADG & FCR, food safety meat withdrawal clearance guard, abattoir dressing %, fleece micron grading'],
                ['name' => 'Pasture & Climate THI', 'description' => 'Rotational grazing paddocks, biomass dry matter utilization, barn NRC THI microclimate alerts'],
                ['name' => 'Inventory & Asset Machinery', 'description' => 'Multi-warehouse stock, inventory receipts/issues, farm machinery runtime and preventative maintenance'],
                ['name' => 'Direct Sales CRM & Subscriptions', 'description' => 'Prepaid customer wallets, daily raw milk subscriptions, route manifests, OTP proof-of-delivery'],
                ['name' => 'Financial Accounting & IAS-41', 'description' => 'Double-entry Chart of Accounts, General Ledger vouchers, true Cost-Per-Liter engine, biological asset appraisal'],
                ['name' => 'Compliance & Traceability', 'description' => 'Punjab Food Authority packs, animal movement permits, bidirectional forward/backward trace, Halal Tazkiyah certification'],
                ['name' => 'IoT Hardware & Mobile Offline Sync', 'description' => 'Telemetry ingestion for scales and meters, offline delta sync with idempotency deduplication'],
            ],
            'paths' => $this->buildPaths(),
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum Token',
                        'description' => 'Enter token obtained via /auth/login',
                    ],
                ],
                'schemas' => $this->buildSchemas(),
            ],
            'security' => [
                ['BearerAuth' => []],
            ],
        ];

        $targetDir = public_path('docs');
        if (! File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $jsonContent = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put($targetDir.'/openapi.json', $jsonContent);

        $this->info("OpenAPI Specification successfully written to {$targetDir}/openapi.json (".strlen($jsonContent).' bytes)');

        return 0;
    }

    private function buildPaths(): array
    {
        return [
            '/system/health' => [
                'get' => [
                    'tags' => ['System Health'],
                    'summary' => 'System diagnostic health check',
                    'security' => [],
                    'responses' => [
                        '200' => ['description' => 'System and database operational'],
                        '503' => ['description' => 'System degraded'],
                    ],
                ],
            ],
            '/docs/openapi.json' => [
                'get' => [
                    'tags' => ['System Health'],
                    'summary' => 'OpenAPI 3.0 specification document',
                    'security' => [],
                    'responses' => [
                        '200' => ['description' => 'OpenAPI JSON schema definition'],
                    ],
                ],
            ],
            '/auth/login' => [
                'post' => [
                    'tags' => ['Authentication & RBAC'],
                    'summary' => 'User login and Sanctum bearer token issue',
                    'security' => [],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['email', 'password'],
                                    'properties' => [
                                        'email' => ['type' => 'string', 'example' => 'owner@pilotfarm.local'],
                                        'password' => ['type' => 'string', 'example' => 'FarmPass2026!'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Authenticated successfully with token'],
                        '401' => ['description' => 'Invalid credentials'],
                    ],
                ],
            ],
            '/user/preferences' => [
                'get' => [
                    'tags' => ['Authentication & RBAC'],
                    'summary' => 'Retrieve current user preferences and supported options catalog (locales, currencies, timezones, date formats)',
                    'responses' => [
                        '200' => ['description' => 'User preferences and supported options returned'],
                    ],
                ],
                'put' => [
                    'tags' => ['Authentication & RBAC'],
                    'summary' => 'Update user preferences (language, timezone, date format, time format, currency)',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['locale', 'timezone', 'date_format', 'time_format', 'currency'],
                                    'properties' => [
                                        'locale' => ['type' => 'string', 'enum' => ['en', 'ur', 'ar', 'es', 'fr'], 'example' => 'en'],
                                        'timezone' => ['type' => 'string', 'example' => 'Asia/Karachi'],
                                        'date_format' => ['type' => 'string', 'enum' => ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd M Y', 'jS F Y'], 'example' => 'd M Y'],
                                        'time_format' => ['type' => 'string', 'enum' => ['12h', '24h'], 'example' => '12h'],
                                        'currency' => ['type' => 'string', 'enum' => ['PKR', 'USD', 'EUR', 'AED', 'SAR', 'GBP'], 'example' => 'PKR'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Preferences saved and applied'],
                        '422' => ['description' => 'Validation error'],
                    ],
                ],
            ],
            '/user/preferences/preview' => [
                'post' => [
                    'tags' => ['Authentication & RBAC'],
                    'summary' => 'Preview localized date, time, number, and currency formatting without saving',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['locale', 'timezone', 'date_format', 'time_format', 'currency'],
                                    'properties' => [
                                        'locale' => ['type' => 'string', 'example' => 'ur'],
                                        'timezone' => ['type' => 'string', 'example' => 'Asia/Karachi'],
                                        'date_format' => ['type' => 'string', 'example' => 'd-m-Y'],
                                        'time_format' => ['type' => 'string', 'example' => '12h'],
                                        'currency' => ['type' => 'string', 'example' => 'PKR'],
                                        'sample_amount' => ['type' => 'number', 'example' => 250000.0],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Formatted preview strings returned'],
                    ],
                ],
            ],
            '/animals' => [
                'get' => [
                    'tags' => ['Livestock & Pedigree'],
                    'summary' => 'List animals with species, breed, and lifecycle stage filters',
                    'parameters' => [
                        ['name' => 'species_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
                        ['name' => 'lifecycle_stage', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Paginated list of animals'],
                    ],
                ],
                'post' => [
                    'tags' => ['Livestock & Pedigree'],
                    'summary' => 'Register a new animal into herd master',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/AnimalCreateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Animal registered successfully'],
                        '422' => ['description' => 'Validation error (e.g. tag conflict)'],
                    ],
                ],
            ],
            '/animals/{id}' => [
                'get' => [
                    'tags' => ['Livestock & Pedigree'],
                    'summary' => 'Get animal details including pedigree and active health status',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Animal record returned'],
                        '404' => ['description' => 'Animal not found'],
                    ],
                ],
            ],
            '/animals/{id}/weight' => [
                'post' => [
                    'tags' => ['Livestock & Pedigree'],
                    'summary' => 'Record weight and calculate Average Daily Gain (ADG)',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['weight_kg', 'recorded_at'],
                                    'properties' => [
                                        'weight_kg' => ['type' => 'number', 'example' => 460.5],
                                        'recorded_at' => ['type' => 'string', 'format' => 'date', 'example' => '2026-10-01'],
                                        'weighing_method' => ['type' => 'string', 'example' => 'scale'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Weight logged and ADG computed'],
                    ],
                ],
            ],
            '/animals/{id}/bcs' => [
                'post' => [
                    'tags' => ['Livestock & Pedigree'],
                    'summary' => 'Log Body Condition Score on standard 1-5 scale (0.25 increments)',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['score', 'assessment_date'],
                                    'properties' => [
                                        'score' => ['type' => 'number', 'example' => 3.25],
                                        'assessment_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-10-01'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'BCS score logged'],
                    ],
                ],
            ],
            '/milk/records' => [
                'get' => [
                    'tags' => ['Dairy Production & Quality'],
                    'summary' => 'Query milk production records with quality status and withholding flags',
                    'responses' => [
                        '200' => ['description' => 'Milk production records'],
                    ],
                ],
                'post' => [
                    'tags' => ['Dairy Production & Quality'],
                    'summary' => 'Log milk yield for an animal; automatically verifies and enforces antimicrobial withholding',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/MilkRecordCreateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Milk record created (safe or auto-discarded if in withdrawal)'],
                    ],
                ],
            ],
            '/milk/withholding-status/{animalId}' => [
                'get' => [
                    'tags' => ['Dairy Production & Quality'],
                    'summary' => 'Check active antimicrobial withdrawal status for an animal',
                    'parameters' => [
                        ['name' => 'animalId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Withholding clearance status and active treatments'],
                    ],
                ],
            ],
            '/collection-centers/intakes' => [
                'post' => [
                    'tags' => ['Cooperative Collection Centers'],
                    'summary' => 'Record smallholder milk intake with Richmond formula SNF, 2D rate chart pricing, and 6-panel adulteration screening',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/MilkCollectionIntakeRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Intake processed with payout calculations or rejected on adulteration'],
                    ],
                ],
            ],
            '/feed/formulations/optimize' => [
                'post' => [
                    'tags' => ['Feed, Nutrition & Silage'],
                    'summary' => 'Run NRC 2001 least-cost ration balancing optimizer for dairy cattle or goats',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['species_type', 'target_stage', 'body_weight_kg', 'target_yield_liters'],
                                    'properties' => [
                                        'species_type' => ['type' => 'string', 'example' => 'cattle'],
                                        'target_stage' => ['type' => 'string', 'example' => 'lactating_high'],
                                        'body_weight_kg' => ['type' => 'number', 'example' => 520.0],
                                        'target_yield_liters' => ['type' => 'number', 'example' => 25.0],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Balanced least-cost ration and nutrient matrix'],
                    ],
                ],
            ],
            '/health/cases' => [
                'post' => [
                    'tags' => ['Veterinary Health & Biosecurity'],
                    'summary' => 'Initiate clinical veterinary case using SOAP diagnostic protocol',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/HealthCaseCreateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Clinical case opened and assigned'],
                    ],
                ],
            ],
            '/health/treatments' => [
                'post' => [
                    'tags' => ['Veterinary Health & Biosecurity'],
                    'summary' => 'Administer veterinary medicine; updates WHO DDDA metrics and locks milk/meat withholdings',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/TreatmentCreateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Treatment logged, withholdings locked'],
                        '422' => ['description' => 'WHO CIA prescription required or dosage invalid'],
                    ],
                ],
            ],
            '/breeding/events' => [
                'post' => [
                    'tags' => ['Reproduction & Calving'],
                    'summary' => 'Record artificial insemination or natural service with semen straw inventory depletion',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/BreedingEventCreateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Breeding event recorded and semen deducted'],
                    ],
                ],
            ],
            '/meat/feedlots/{id}/gain' => [
                'post' => [
                    'tags' => ['Meat, Feedlot & Fleece'],
                    'summary' => 'Record weigh-in for finishing pen steer/heifer and compute FCR and cost/kg gain',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Metrics updated: ADG, FCR, cost per kg gain'],
                    ],
                ],
            ],
            '/meat/slaughter/clearance/{animalId}' => [
                'get' => [
                    'tags' => ['Meat, Feedlot & Fleece'],
                    'summary' => 'Verify Food Safety Meat Withdrawal Clearance Guard prior to slaughter authorization',
                    'parameters' => [
                        ['name' => 'animalId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Clearance verified or blocked with days remaining'],
                    ],
                ],
            ],
            '/climate/thi-readings' => [
                'get' => [
                    'tags' => ['Pasture & Climate THI'],
                    'summary' => 'Get barn microclimate telemetry and NRC THI heat stress risk assessment',
                    'responses' => [
                        '200' => ['description' => 'THI index and actuator dispatch recommendations'],
                    ],
                ],
            ],
            '/sales/customers/{id}/topup' => [
                'post' => [
                    'tags' => ['Direct Sales CRM & Subscriptions'],
                    'summary' => 'Top up customer prepaid wallet via JazzCash/EasyPaisa/Bank Transfer',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Wallet balance incremented with ledger audit reference'],
                    ],
                ],
            ],
            '/sales/delivery-runs/generate' => [
                'post' => [
                    'tags' => ['Direct Sales CRM & Subscriptions'],
                    'summary' => 'Generate daily delivery run route manifest from active household subscriptions',
                    'responses' => [
                        '201' => ['description' => 'Run manifest created with sequenced customer stops'],
                    ],
                ],
            ],
            '/finance/chart-of-accounts' => [
                'get' => [
                    'tags' => ['Financial Accounting & IAS-41'],
                    'summary' => 'Retrieve hierarchical Chart of Accounts (Assets, Liabilities, Equity, Revenues, Expenses)',
                    'responses' => [
                        '200' => ['description' => 'Chart of Accounts tree'],
                    ],
                ],
                'post' => [
                    'tags' => ['Financial Accounting & IAS-41'],
                    'summary' => 'Create a custom ledger account',
                    'responses' => [
                        '201' => ['description' => 'Account created'],
                    ],
                ],
            ],
            '/finance/cost-per-liter' => [
                'get' => [
                    'tags' => ['Financial Accounting & IAS-41'],
                    'summary' => 'True Cost-Per-Liter (CPL) analytical calculation and operating net margin',
                    'parameters' => [
                        ['name' => 'labor_energy_overheads', 'in' => 'query', 'schema' => ['type' => 'number']],
                        ['name' => 'selling_price_benchmark', 'in' => 'query', 'schema' => ['type' => 'number']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'CPL breakdown by feed, healthcare, overheads, and net margin'],
                    ],
                ],
            ],
            '/finance/biological-valuations/{animalId}/appraise' => [
                'post' => [
                    'tags' => ['Financial Accounting & IAS-41'],
                    'summary' => 'Appraise animal under IAS-41 Agriculture fair value standard and post General Ledger voucher',
                    'parameters' => [
                        ['name' => 'animalId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Animal appraised and journal voucher recorded'],
                    ],
                ],
            ],
            '/compliance/traceability/forward/{animalId}' => [
                'get' => [
                    'tags' => ['Compliance & Traceability'],
                    'summary' => 'Forward Trace: animal -> health treatments & withholdings -> milk yield -> abattoir carcass',
                    'parameters' => [
                        ['name' => 'animalId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Forward traceability chain report'],
                    ],
                ],
            ],
            '/compliance/traceability/backward/{deliveryStopId}' => [
                'get' => [
                    'tags' => ['Compliance & Traceability'],
                    'summary' => 'Backward Trace: delivery stop -> cold chain vehicle -> bulk tank -> contributing cows',
                    'parameters' => [
                        ['name' => 'deliveryStopId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Backward farm-to-table traceability report'],
                    ],
                ],
            ],
            '/compliance/halal-certifications' => [
                'post' => [
                    'tags' => ['Compliance & Traceability'],
                    'summary' => 'Certify Islamic Halal slaughter (Tazkiyah non-stun, Tasmiyah recited, jugular cut)',
                    'responses' => [
                        '201' => ['description' => 'Halal slaughter certificate issued'],
                    ],
                ],
            ],
            '/sync/pull' => [
                'get' => [
                    'tags' => ['IoT Hardware & Mobile Offline Sync'],
                    'summary' => 'Mobile offline delta pull: fetch server changes since last client revision ID',
                    'parameters' => [
                        ['name' => 'last_rev_id', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 0]],
                        ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 100]],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Revision changelog and latest revision ID'],
                    ],
                ],
            ],
            '/sync/push' => [
                'post' => [
                    'tags' => ['IoT Hardware & Mobile Offline Sync'],
                    'summary' => 'Mobile offline push mutations with idempotency key deduplication',
                    'responses' => [
                        '200' => ['description' => 'Mutations processed or skipped if duplicate'],
                    ],
                ],
            ],
        ];
    }

    private function buildSchemas(): array
    {
        return [
            'AnimalCreateRequest' => [
                'type' => 'object',
                'required' => ['tag_number', 'species_id', 'breed_id', 'gender', 'birth_date'],
                'properties' => [
                    'tag_number' => ['type' => 'string', 'example' => 'PK-COW-101'],
                    'electronic_id' => ['type' => 'string', 'example' => '982000412891999'],
                    'species_id' => ['type' => 'integer', 'example' => 1],
                    'breed_id' => ['type' => 'integer', 'example' => 1],
                    'gender' => ['type' => 'string', 'enum' => ['female', 'male'], 'example' => 'female'],
                    'birth_date' => ['type' => 'string', 'format' => 'date', 'example' => '2024-03-15'],
                    'lifecycle_stage' => ['type' => 'string', 'example' => 'heifer'],
                ],
            ],
            'MilkRecordCreateRequest' => [
                'type' => 'object',
                'required' => ['animal_id', 'recorded_date', 'shift', 'yield_liters'],
                'properties' => [
                    'animal_id' => ['type' => 'integer', 'example' => 1],
                    'recorded_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-10-01'],
                    'shift' => ['type' => 'string', 'enum' => ['morning', 'afternoon', 'evening'], 'example' => 'morning'],
                    'yield_liters' => ['type' => 'number', 'example' => 14.5],
                    'fat_percentage' => ['type' => 'number', 'example' => 4.2],
                    'snf_percentage' => ['type' => 'number', 'example' => 8.9],
                    'scc' => ['type' => 'integer', 'example' => 120],
                ],
            ],
            'MilkCollectionIntakeRequest' => [
                'type' => 'object',
                'required' => ['collection_center_id', 'farmer_supplier_id', 'rate_chart_id', 'shift', 'gross_volume_liters', 'lactometer_reading', 'fat_percentage'],
                'properties' => [
                    'collection_center_id' => ['type' => 'integer', 'example' => 1],
                    'farmer_supplier_id' => ['type' => 'integer', 'example' => 1],
                    'rate_chart_id' => ['type' => 'integer', 'example' => 1],
                    'shift' => ['type' => 'string', 'enum' => ['morning', 'evening'], 'example' => 'morning'],
                    'species_type' => ['type' => 'string', 'enum' => ['cow', 'buffalo'], 'example' => 'cow'],
                    'gross_volume_liters' => ['type' => 'number', 'example' => 45.0],
                    'lactometer_reading' => ['type' => 'number', 'example' => 29.0],
                    'fat_percentage' => ['type' => 'number', 'example' => 4.2],
                ],
            ],
            'HealthCaseCreateRequest' => [
                'type' => 'object',
                'required' => ['animal_id', 'case_type', 'subjective_symptoms', 'objective_findings'],
                'properties' => [
                    'animal_id' => ['type' => 'integer', 'example' => 1],
                    'case_type' => ['type' => 'string', 'example' => 'clinical_mastitis'],
                    'subjective_symptoms' => ['type' => 'string', 'example' => 'Swollen left rear quarter, abnormal clots in milk'],
                    'objective_findings' => ['type' => 'string', 'example' => 'Rectal temp 39.8C, milk conductivity 7.2 mS/cm'],
                ],
            ],
            'TreatmentCreateRequest' => [
                'type' => 'object',
                'required' => ['animal_id', 'medicine_id', 'dosage', 'dosage_unit', 'route'],
                'properties' => [
                    'animal_id' => ['type' => 'integer', 'example' => 1],
                    'medicine_id' => ['type' => 'integer', 'example' => 1],
                    'dosage' => ['type' => 'number', 'example' => 20.0],
                    'dosage_unit' => ['type' => 'string', 'example' => 'ml'],
                    'route' => ['type' => 'string', 'example' => 'intramuscular'],
                    'cost' => ['type' => 'number', 'example' => 450.0],
                ],
            ],
            'BreedingEventCreateRequest' => [
                'type' => 'object',
                'required' => ['animal_id', 'event_type', 'event_date'],
                'properties' => [
                    'animal_id' => ['type' => 'integer', 'example' => 1],
                    'event_type' => ['type' => 'string', 'enum' => ['ai_insemination', 'natural_service'], 'example' => 'ai_insemination'],
                    'event_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-10-01'],
                    'semen_straw_inventory_id' => ['type' => 'integer', 'example' => 1],
                ],
            ],
        ];
    }
}
