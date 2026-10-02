<?php

namespace Tests\Unit;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Services\FleeceGradingClassifier;
use App\Domain\Climate\Services\NrcThiCalculator;
use App\Domain\Feed\Services\DryMatterIntakeCalculator;
use App\Domain\Finance\Services\Ias41BiologicalValuationService;
use App\Domain\Health\Models\Medicine;
use App\Domain\Health\Services\AntimicrobialUsageCalculator;
use App\Domain\Milk\Models\MilkRateChart;
use App\Domain\Milk\Services\TwoDimensionalRateChartPricingCalculator;
use Tests\TestCase;

class AgritechCalculationEnginesTest extends TestCase
{
    public function test_nrc_temperature_humidity_index_calculation_and_heat_stress_categories(): void
    {
        $calculator = new NrcThiCalculator;

        // 1. Comfortable: 21°C at 50% Relative Humidity
        $thiComfort = $calculator->calculateThi(21.0, 50.0);
        $this->assertLessThan(72.0, $thiComfort);

        $assessmentComfort = $calculator->assessHeatStress(21.0, 50.0, 'cattle');
        $this->assertEquals('comfortable', $assessmentComfort['heat_stress_level']);
        $this->assertFalse($assessmentComfort['cooling_actuator_activated']);

        // 2. Mild Stress: 27°C at 65% RH
        $thiMild = $calculator->calculateThi(27.0, 65.0);
        $this->assertGreaterThanOrEqual(72.0, $thiMild);
        $this->assertLessThan(79.0, $thiMild);

        $assessmentMild = $calculator->assessHeatStress(27.0, 65.0, 'cattle');
        $this->assertEquals('mild_stress', $assessmentMild['heat_stress_level']);

        // 3. Moderate Stress: 32°C at 70% RH
        $assessmentModerate = $calculator->assessHeatStress(32.0, 70.0, 'cattle');
        $this->assertEquals('moderate_stress', $assessmentModerate['heat_stress_level']);
        $this->assertTrue($assessmentModerate['cooling_actuator_activated']);

        // 4. Emergency / Severe Stress: 38°C at 80% RH
        $assessmentSevere = $calculator->assessHeatStress(38.0, 80.0, 'cattle');
        $this->assertContains($assessmentSevere['heat_stress_level'], ['severe_stress', 'emergency_danger']);
        $this->assertTrue($assessmentSevere['cooling_actuator_activated']);

        // 5. Small Ruminant (Goat) Thermal Resilience: 27°C / 65% RH is comfortable for goats (threshold ~78 vs 72)
        $assessmentGoat = $calculator->assessHeatStress(27.0, 65.0, 'goat');
        $this->assertEquals('comfortable', $assessmentGoat['heat_stress_level']);
    }

    public function test_richmond_formula_solids_not_fat_calculation(): void
    {
        $calculator = new TwoDimensionalRateChartPricingCalculator;

        // Cow: SNF = (CLR / 4) + (0.25 * Fat) + 0.35
        // CLR = 29.0, Fat = 4.2%
        // SNF = (29.0 / 4) + (0.25 * 4.2) + 0.35 = 7.25 + 1.05 + 0.35 = 8.65%
        $cowSnf = $calculator->calculateSnfFromLactometer(29.0, 4.2, 'cow');
        $this->assertEquals(8.65, $cowSnf);

        // Buffalo: SNF = (CLR / 4) + (0.20 * Fat) + 0.60
        // CLR = 30.0, Fat = 6.5%
        // SNF = (30.0 / 4) + (0.20 * 6.5) + 0.60 = 7.50 + 1.30 + 0.60 = 9.40%
        $buffaloSnf = $calculator->calculateSnfFromLactometer(30.0, 6.5, 'buffalo');
        $this->assertEquals(9.40, $buffaloSnf);
    }

    public function test_adulteration_battery_strict_rejection(): void
    {
        $calculator = new TwoDimensionalRateChartPricingCalculator;

        // Mock rate chart object without database
        $chart = new MilkRateChart;
        $chart->base_price_per_liter = 140.00;
        $chart->standard_fat_percentage = 3.50;
        $chart->standard_snf_percentage = 8.50;
        $chart->fat_rate_per_unit = 12.00;
        $chart->snf_rate_per_unit = 8.00;
        $chart->minimum_fat_percentage = 3.00;
        $chart->minimum_snf_percentage = 8.00;

        // Alcohol test curdled positive
        $result = $calculator->calculateIntakePrice(
            chart: $chart,
            volumeLiters: 50.0,
            fatPercentage: 4.0,
            snfPercentage: 8.8,
            adulterationTests: ['alcohol_test_result' => 'positive']
        );

        $this->assertFalse($result['quality_accepted']);
        $this->assertEquals(0.0, $result['net_payable_amount']);
        $this->assertStringContainsString('68% Alcohol', $result['rejection_reason']);

        // Formalin adulteration detected
        $resultFormalin = $calculator->calculateIntakePrice(
            chart: $chart,
            volumeLiters: 50.0,
            fatPercentage: 4.0,
            snfPercentage: 8.8,
            adulterationTests: ['adulteration_formalin' => true]
        );

        $this->assertFalse($resultFormalin['quality_accepted']);
        $this->assertStringContainsString('Formalin', $resultFormalin['rejection_reason']);
    }

    public function test_nrc_2001_fat_corrected_milk_and_dry_matter_intake_prediction(): void
    {
        $calculator = new DryMatterIntakeCalculator;

        // 4% FCM Gainsborough Formula: (0.4 * Yield) + (15 * (Fat% / 100) * Yield)
        // 25.0 kg yield at 3.8% fat
        // 0.4 * 25.0 = 10.0
        // 15 * 0.038 * 25.0 = 14.25
        // FCM = 24.25 kg
        $fcm = $calculator->calculateFatCorrectedMilk(25.0, 3.8);
        $this->assertEquals(24.25, $fcm);

        // Predict DMI for 550kg cow yielding 25kg milk
        $prediction = $calculator->predictDairyCattleDmi(
            bodyWeightKg: 550.0,
            milkYieldKg: 25.0,
            fatPercentage: 3.8,
            daysInMilk: 90
        );

        $this->assertGreaterThan(15.0, $prediction['predicted_dmi_kg']);
        $this->assertLessThan(26.0, $prediction['predicted_dmi_kg']);
        $this->assertEquals(24.25, $prediction['fcm_4_percent_kg']);
        $this->assertGreaterThan(2.5, $prediction['dmi_percent_body_weight']);
    }

    public function test_fleece_micron_grading_international_tier_classification(): void
    {
        $classifier = new FleeceGradingClassifier;

        $this->assertEquals('ultrafine', $classifier->classifyTier(16.8));
        $this->assertEquals('superfine', $classifier->classifyTier(18.2));
        $this->assertEquals('fine', $classifier->classifyTier(19.4));
        $this->assertEquals('medium', $classifier->classifyTier(21.8));
        $this->assertEquals('coarse', $classifier->classifyTier(28.5));

        $grade = $classifier->gradeFleece(
            greaseWeightKg: 4.50,
            micronGrade: 18.0,
            dirtYieldDeductionPercent: 30.0
        );

        $this->assertEquals(70.0, $grade['clean_yield_percentage']);
        $this->assertEquals(3.15, $grade['clean_fleece_weight_kg']);
        $this->assertEquals('superfine', $grade['quality_tier']);
    }

    public function test_who_antimicrobial_usage_ddda_metric_calculation(): void
    {
        $calculator = new AntimicrobialUsageCalculator;

        $medicine = new Medicine;
        $medicine->is_antimicrobial = true;
        $medicine->standard_ddda_mg_per_kg = 10.0; // 10 mg/kg standard daily dose

        // Treatment of 400kg animal with 2000mg active substance:
        // DDDA = 2000 / (10 * 400) = 2000 / 4000 = 0.500 DDDA
        $ddda = $calculator->calculateTreatmentDdda(
            medicine: $medicine,
            activeSubstanceMg: 2000.0,
            animalWeightKg: 400.0
        );

        $this->assertEquals(0.500, $ddda);

        // Non-antimicrobial medicine should return 0.0 DDDA
        $nonAntimicrobial = new Medicine;
        $nonAntimicrobial->is_antimicrobial = false;

        $zeroDdda = $calculator->calculateTreatmentDdda(
            medicine: $nonAntimicrobial,
            activeSubstanceMg: 5000.0,
            animalWeightKg: 400.0
        );

        $this->assertEquals(0.0, $zeroDdda);
    }

    public function test_ias41_biological_asset_fair_value_and_estimated_cost_to_sell(): void
    {
        $valuationService = new Ias41BiologicalValuationService;

        $animal = new Animal;
        $animal->lifecycle_stage = 'lactating';
        $animal->current_weight_kg = 480.0;

        $valuation = $valuationService->computeAnimalFairValue($animal);

        $this->assertEquals(320000.00, $valuation['fair_value']);
        // 5% standard estimated selling costs
        $this->assertEquals(16000.00, $valuation['cost_to_sell']);
        $this->assertEquals(304000.00, $valuation['net_carrying_value']);
        $this->assertEquals('lactating', $valuation['maturity_stage']);
    }
}
