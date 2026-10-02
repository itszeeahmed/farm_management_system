<?php

use App\Http\Controllers\Api\v1\AccountingAndFinanceController;
use App\Http\Controllers\Api\v1\AnimalController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BreedingController;
use App\Http\Controllers\Api\v1\ClimateController;
use App\Http\Controllers\Api\v1\CollectionCenterController;
use App\Http\Controllers\Api\v1\ComplianceAndTraceabilityController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\FeedController;
use App\Http\Controllers\Api\v1\FinanceController;
use App\Http\Controllers\Api\v1\HealthController;
use App\Http\Controllers\Api\v1\HierarchyController;
use App\Http\Controllers\Api\v1\InventoryController;
use App\Http\Controllers\Api\v1\MeatAndSpeciesController;
use App\Http\Controllers\Api\v1\MilkController;
use App\Http\Controllers\Api\v1\OnboardingController;
use App\Http\Controllers\Api\v1\PastureAndClimateController;
use App\Http\Controllers\Api\v1\SubscriptionAndSalesController;
use App\Http\Controllers\Api\v1\SyncAndHardwareController;
use App\Http\Controllers\Api\v1\SystemHealthController;
use App\Http\Controllers\Api\v1\TaskController;
use App\Http\Controllers\Api\v1\UserManagementController;
use App\Http\Controllers\Api\v1\UserPreferenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware(['resolve.farm'])->group(function () {
    // Authentication & Multi-Tenant Farms
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/farms', [AuthController::class, 'farms']);

    // SaaS Onboarding (New Farm Enterprise Self-Registration)
    Route::post('/onboarding/register', [OnboardingController::class, 'register']);

    // Team & User Management (Role Delegation & Access Grants)
    Route::get('/team', [UserManagementController::class, 'index'])->middleware('farm.permission:farm_owner,team.manage,audit.view');
    Route::post('/team/invite', [UserManagementController::class, 'invite'])->middleware('farm.permission:farm_owner,team.manage');
    Route::put('/team/{id}/role', [UserManagementController::class, 'updateRole'])->middleware('farm.permission:farm_owner,team.manage');
    Route::delete('/team/{id}', [UserManagementController::class, 'revoke'])->middleware('farm.permission:farm_owner,team.manage');

    // System Health & OpenAPI Documentation
    Route::get('/system/health', [SystemHealthController::class, 'health']);
    Route::get('/docs/openapi.json', [SystemHealthController::class, 'openapiJson']);

    // User Settings & Internationalization Preferences (Locales, Currencies, Timezones, Date Formats)
    Route::get('/user/preferences', [UserPreferenceController::class, 'getPreferences']);
    Route::put('/user/preferences', [UserPreferenceController::class, 'updatePreferences']);
    Route::post('/user/preferences', [UserPreferenceController::class, 'updatePreferences']);
    Route::post('/user/preferences/preview', [UserPreferenceController::class, 'preview']);

    // Dashboard & Metrics
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Organization Hierarchy, Zones, Structures, and RBAC
    Route::get('/hierarchy', [HierarchyController::class, 'index']);
    Route::get('/roles', [HierarchyController::class, 'roles']);
    Route::get('/animal-groups', [HierarchyController::class, 'groups']);
    Route::get('/audit-logs', [HierarchyController::class, 'auditLogs']);

    // Animals & Herd Management
    Route::get('/animals', [AnimalController::class, 'index']);
    Route::get('/animals/{id}', [AnimalController::class, 'show']);
    Route::get('/animals/{id}/pedigree', [AnimalController::class, 'pedigree']);
    Route::post('/animals/{id}/weights', [AnimalController::class, 'recordWeight'])->middleware('farm.permission:animals.update');
    Route::post('/animals/{id}/bcs', [AnimalController::class, 'recordBcs'])->middleware('farm.permission:animals.update');
    Route::post('/animals/{id}/lifecycle-transition', [AnimalController::class, 'transitionLifecycle'])->middleware('farm.permission:animals.update');
    Route::post('/animals', [AnimalController::class, 'store'])->middleware('farm.permission:animals.create');
    Route::put('/animals/{id}', [AnimalController::class, 'update'])->middleware('farm.permission:animals.update');
    Route::delete('/animals/{id}', [AnimalController::class, 'destroy'])->middleware('farm.permission:animals.delete');

    // Milk Recording, Bulk Tanks & Dispatches
    Route::get('/milk', [MilkController::class, 'index']);
    Route::post('/milk/record', [MilkController::class, 'storeRecord'])->middleware('farm.permission:milk.record');
    Route::get('/milk/summary', [MilkController::class, 'summary']);
    Route::get('/milk/bulk-tanks', [MilkController::class, 'bulkTanks']);
    Route::post('/milk/bulk-tanks/{id}/cip-clean', [MilkController::class, 'cipClean']);
    Route::get('/milk/dispatches', [MilkController::class, 'dispatches']);
    Route::post('/milk/dispatches', [MilkController::class, 'storeDispatch'])->middleware('farm.permission:milk.approve');

    // Cooperative Milk Collection Centers (MCC) & 2D Rate Chart Pricing
    Route::get('/collection-centers', [CollectionCenterController::class, 'centers']);
    Route::get('/collection-centers/{id}', [CollectionCenterController::class, 'showCenter']);
    Route::get('/suppliers', [CollectionCenterController::class, 'suppliers']);
    Route::post('/suppliers', [CollectionCenterController::class, 'storeSupplier']);
    Route::get('/rate-charts', [CollectionCenterController::class, 'rateCharts']);
    Route::post('/rate-charts', [CollectionCenterController::class, 'storeRateChart']);
    Route::get('/intakes', [CollectionCenterController::class, 'intakes']);
    Route::post('/intakes', [CollectionCenterController::class, 'storeIntake']);

    // Health, Veterinary, SOAP Diagnoses & Antimicrobial Stewardship
    Route::get('/health', [HealthController::class, 'index']);
    Route::get('/health/medicines', [HealthController::class, 'medicines']);
    Route::post('/health/cases', [HealthController::class, 'storeCase'])->middleware('farm.permission:health.diagnose');
    Route::post('/health/treatments', [HealthController::class, 'storeTreatment'])->middleware('farm.permission:health.administer');
    Route::get('/health/amu-summary', [HealthController::class, 'amuSummary']);
    Route::get('/health/biosecurity-audits', [HealthController::class, 'biosecurityAudits']);
    Route::post('/health/biosecurity-audits', [HealthController::class, 'storeBiosecurityAudit']);

    // Breeding, Liquid Nitrogen Semen, Calving Lifecycle & KPIs
    Route::get('/breeding', [BreedingController::class, 'index']);
    Route::post('/breeding/events', [BreedingController::class, 'storeEvent']);
    Route::get('/breeding/semen-inventory', [BreedingController::class, 'semenInventory']);
    Route::post('/breeding/semen-inventory', [BreedingController::class, 'storeSemenStraw']);
    Route::post('/breeding/calving', [BreedingController::class, 'recordCalving']);
    Route::get('/breeding/postpartum-checks', [BreedingController::class, 'postpartumChecks']);
    Route::get('/breeding/kpis', [BreedingController::class, 'kpis']);

    // Feeds, Formulations, TMR Batch Mixer, Bunk Scoring & DMI
    Route::get('/feeds', [FeedController::class, 'index']);
    Route::post('/feeds/consumption', [FeedController::class, 'storeConsumption'])->middleware('farm.permission:feed.manage');
    Route::get('/feeds/formulations', [FeedController::class, 'formulations']);
    Route::post('/feeds/formulations', [FeedController::class, 'storeFormulation'])->middleware('farm.permission:feed.manage');
    Route::get('/feeds/tmr-batches', [FeedController::class, 'tmrBatches']);
    Route::post('/feeds/tmr-batches', [FeedController::class, 'storeTmrBatch']);
    Route::get('/feeds/bunk-scores', [FeedController::class, 'bunkScores']);
    Route::post('/feeds/bunk-scores', [FeedController::class, 'storeBunkScore']);
    Route::get('/feeds/silage-bunkers', [FeedController::class, 'silageBunkers']);
    Route::post('/feeds/predict-dmi', [FeedController::class, 'predictDmi']);

    // Finance, Revenue & Cost-per-Liter
    Route::get('/finances', [FinanceController::class, 'index']);
    Route::post('/finances/transactions', [FinanceController::class, 'storeTransaction'])->middleware('farm.permission:finance.transact');

    // Climate & NRC THI Heat Stress Index
    Route::get('/climate/current', [ClimateController::class, 'current']);
    Route::post('/climate/readings', [ClimateController::class, 'store']);

    // Workforce & Daily Farm Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // Phase 4: Beef Feedlot, Slaughter & Fiber/Species
    Route::get('/meat/feedlots', [MeatAndSpeciesController::class, 'feedlotRecords']);
    Route::post('/meat/feedlots', [MeatAndSpeciesController::class, 'recordFeedlotIntake']);
    Route::post('/meat/feedlots/{id}/gain', [MeatAndSpeciesController::class, 'updateFeedlotGain']);
    Route::get('/meat/slaughter/clearance/{animalId}', [MeatAndSpeciesController::class, 'checkSlaughterClearance']);
    Route::post('/meat/slaughter', [MeatAndSpeciesController::class, 'recordSlaughter']);
    Route::get('/meat/fleeces', [MeatAndSpeciesController::class, 'fleeceRecords']);
    Route::post('/meat/fleeces', [MeatAndSpeciesController::class, 'recordFleeceShearing']);
    Route::get('/meat/specialized-species', [MeatAndSpeciesController::class, 'specializedSpecies']);
    Route::post('/meat/specialized-species/{animalId}', [MeatAndSpeciesController::class, 'updateSpecializedSpecies']);

    // Phase 4: Rotational Pasture & Advanced Climate THI
    Route::get('/pastures/paddocks', [PastureAndClimateController::class, 'paddocks']);
    Route::post('/pastures/paddocks', [PastureAndClimateController::class, 'createPaddock']);
    Route::post('/pastures/grazing/enter', [PastureAndClimateController::class, 'enterPaddock']);
    Route::post('/pastures/grazing/{id}/exit', [PastureAndClimateController::class, 'exitPaddock']);
    Route::post('/climate/sensor-readings', [PastureAndClimateController::class, 'recordClimateReading']);
    Route::get('/climate/history', [PastureAndClimateController::class, 'climateHistory']);

    // Phase 4: Universal Inventory, Warehouses & Assets
    Route::get('/inventory/warehouses', [InventoryController::class, 'warehouses']);
    Route::post('/inventory/warehouses', [InventoryController::class, 'createWarehouse']);
    Route::get('/inventory/items', [InventoryController::class, 'inventoryItems']);
    Route::post('/inventory/items', [InventoryController::class, 'createInventoryItem']);
    Route::post('/inventory/transactions', [InventoryController::class, 'recordTransaction']);
    Route::get('/inventory/assets', [InventoryController::class, 'farmAssets']);
    Route::post('/inventory/assets', [InventoryController::class, 'createFarmAsset']);
    Route::post('/inventory/maintenance', [InventoryController::class, 'recordMaintenance']);

    // Phase 4: Customer Direct Sales, Milk Subscriptions & Delivery Runs
    Route::get('/sales/customers', [SubscriptionAndSalesController::class, 'customers']);
    Route::post('/sales/customers', [SubscriptionAndSalesController::class, 'createCustomer']);
    Route::post('/sales/customers/{id}/topup', [SubscriptionAndSalesController::class, 'topUpWallet']);
    Route::get('/sales/subscriptions', [SubscriptionAndSalesController::class, 'subscriptions']);
    Route::post('/sales/subscriptions', [SubscriptionAndSalesController::class, 'createSubscription']);
    Route::post('/sales/delivery-runs/generate', [SubscriptionAndSalesController::class, 'generateDeliveryRun']);
    Route::get('/sales/delivery-runs', [SubscriptionAndSalesController::class, 'deliveryRuns']);
    Route::get('/sales/delivery-runs/{id}', [SubscriptionAndSalesController::class, 'deliveryRunDetail']);
    Route::post('/sales/delivery-stops/{id}/complete', [SubscriptionAndSalesController::class, 'completeDeliveryStop']);

    // Phase 5: Financial Accounting, Cost-per-Liter & IAS-41 Biological Valuation
    Route::get('/finance/chart-of-accounts', [AccountingAndFinanceController::class, 'chartOfAccounts']);
    Route::post('/finance/chart-of-accounts', [AccountingAndFinanceController::class, 'createAccount']);
    Route::get('/finance/general-ledger', [AccountingAndFinanceController::class, 'generalLedger']);
    Route::post('/finance/general-ledger/journal-voucher', [AccountingAndFinanceController::class, 'recordJournalVoucher']);
    Route::get('/finance/cost-per-liter', [AccountingAndFinanceController::class, 'costPerLiter']);
    Route::get('/finance/biological-valuations', [AccountingAndFinanceController::class, 'biologicalValuations']);
    Route::post('/finance/biological-valuations/{animalId}/appraise', [AccountingAndFinanceController::class, 'appraiseAnimal']);
    Route::get('/finance/cost-allocation-rules', [AccountingAndFinanceController::class, 'costAllocationRules']);

    // Phase 5: Compliance Packs & End-to-End Traceability
    Route::get('/compliance/packs', [ComplianceAndTraceabilityController::class, 'compliancePacks']);
    Route::get('/compliance/movement-permits', [ComplianceAndTraceabilityController::class, 'movementPermits']);
    Route::post('/compliance/movement-permits', [ComplianceAndTraceabilityController::class, 'createMovementPermit']);
    Route::get('/compliance/traceability/forward/{animalId}', [ComplianceAndTraceabilityController::class, 'forwardTrace']);
    Route::get('/compliance/traceability/backward/{deliveryStopId}', [ComplianceAndTraceabilityController::class, 'backwardTrace']);
    Route::get('/compliance/halal-certifications', [ComplianceAndTraceabilityController::class, 'halalCertifications']);
    Route::post('/compliance/halal-certifications', [ComplianceAndTraceabilityController::class, 'recordHalalCertification']);
    Route::post('/compliance/welfare-assessments', [ComplianceAndTraceabilityController::class, 'recordWelfareAssessment']);

    // Phase 5: IoT Hardware Telemetry & Mobile Offline Sync Engine
    Route::get('/sync/devices', [SyncAndHardwareController::class, 'deviceRegistries']);
    Route::post('/sync/devices', [SyncAndHardwareController::class, 'registerDevice']);
    Route::post('/sync/telemetry', [SyncAndHardwareController::class, 'ingestTelemetry']);
    Route::get('/sync/pull', [SyncAndHardwareController::class, 'pullChanges']);
    Route::post('/sync/push', [SyncAndHardwareController::class, 'pushMutations']);
});
