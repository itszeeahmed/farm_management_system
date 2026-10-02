<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Carbon\Carbon;
use Database\Seeders\PilotFarmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

class UserPreferencesAndInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotFarmSeeder::class);
    }

    public function test_get_user_preferences_returns_defaults_and_supported_options_catalog(): void
    {
        $user = User::where('email', 'owner@farm.local')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/v1/user/preferences');

        $response->assertStatus(200)
            ->assertJsonPath('preferences.locale', 'en')
            ->assertJsonPath('preferences.timezone', 'Asia/Karachi')
            ->assertJsonPath('preferences.currency', 'PKR')
            ->assertJsonStructure([
                'preferences' => [
                    'id',
                    'user_id',
                    'locale',
                    'timezone',
                    'date_format',
                    'time_format',
                    'currency',
                    'currency_symbol_position',
                    'decimal_separator',
                    'thousands_separator',
                ],
                'preview' => [
                    'formatted_date',
                    'formatted_time',
                    'formatted_datetime',
                    'formatted_currency',
                    'formatted_number',
                    'sample_translation' => [
                        'dashboard',
                        'animals',
                        'milk_production',
                        'settings',
                        'saved_message',
                    ],
                ],
                'options' => [
                    'supported_locales' => [
                        '*' => ['code', 'name', 'native_name', 'direction'],
                    ],
                    'supported_currencies' => [
                        '*' => ['code', 'symbol', 'name', 'decimals'],
                    ],
                    'supported_date_formats' => [
                        '*' => ['pattern', 'label', 'example'],
                    ],
                    'supported_time_formats' => [
                        '*' => ['code', 'pattern', 'example'],
                    ],
                    'common_timezones',
                ],
            ]);

        $locales = array_column($response->json('options.supported_locales'), 'code');
        $this->assertContains('en', $locales);
        $this->assertContains('ur', $locales);
        $this->assertContains('ar', $locales);
        $this->assertContains('es', $locales);
        $this->assertContains('fr', $locales);

        $currencies = array_column($response->json('options.supported_currencies'), 'code');
        $this->assertContains('PKR', $currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertContains('AED', $currencies);
        $this->assertContains('SAR', $currencies);
        $this->assertContains('GBP', $currencies);
    }

    public function test_update_user_preferences_persists_settings_and_updates_locale(): void
    {
        $user = User::where('email', 'owner@farm.local')->firstOrFail();

        // Update to Arabic, Dubai timezone, verbose date, 12h, AED
        $payload = [
            'locale' => 'ar',
            'timezone' => 'Asia/Dubai',
            'date_format' => 'jS F Y',
            'time_format' => '12h',
            'currency' => 'AED',
            'currency_symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ];

        $response = $this->actingAs($user)->putJson('/api/v1/user/preferences', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('preferences.locale', 'ar')
            ->assertJsonPath('preferences.timezone', 'Asia/Dubai')
            ->assertJsonPath('preferences.currency', 'AED')
            ->assertJsonPath('preferences.date_format', 'jS F Y')
            ->assertJsonPath('preferences.time_format', '12h')
            ->assertJsonPath('message', 'تم حفظ التفضيلات بنجاح.');

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'locale' => 'ar',
            'timezone' => 'Asia/Dubai',
            'currency' => 'AED',
        ]);
    }

    public function test_update_user_preferences_with_urdu_locale_and_lahore_timezone(): void
    {
        $user = User::where('email', 'worker@farm.local')->firstOrFail();

        $payload = [
            'locale' => 'ur',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'd-m-Y',
            'time_format' => '12h',
            'currency' => 'PKR',
        ];

        $response = $this->actingAs($user)->putJson('/api/v1/user/preferences', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('preferences.locale', 'ur')
            ->assertJsonPath('message', 'ترجیحات کامیابی سے محفوظ ہو گئیں۔');
    }

    public function test_update_user_preferences_validation_rejects_invalid_values(): void
    {
        $user = User::firstOrFail();

        // 1. Invalid locale
        $res1 = $this->actingAs($user)->putJson('/api/v1/user/preferences', [
            'locale' => 'invalid_lang',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'currency' => 'PKR',
        ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['locale']);

        // 2. Invalid timezone
        $res2 = $this->actingAs($user)->putJson('/api/v1/user/preferences', [
            'locale' => 'en',
            'timezone' => 'Invalid/Fictional_Zone',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'currency' => 'PKR',
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['timezone']);

        // 3. Invalid currency
        $res3 = $this->actingAs($user)->putJson('/api/v1/user/preferences', [
            'locale' => 'en',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'currency' => 'CRYPTO_COIN',
        ]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['currency']);

        // 4. Invalid date format
        $res4 = $this->actingAs($user)->putJson('/api/v1/user/preferences', [
            'locale' => 'en',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'invalid_pattern',
            'time_format' => '24h',
            'currency' => 'PKR',
        ]);
        $res4->assertStatus(422)->assertJsonValidationErrors(['date_format']);
    }

    public function test_user_preferences_preview_endpoint_without_saving(): void
    {
        $payload = [
            'locale' => 'es',
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'time_format' => '24h',
            'currency' => 'EUR',
            'sample_amount' => 75420.50,
        ];

        $response = $this->postJson('/api/v1/user/preferences/preview', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('locale_info.code', 'es')
            ->assertJsonPath('locale_info.name', 'Spanish')
            ->assertJsonPath('currency_info.code', 'EUR')
            ->assertJsonPath('translated_labels.dashboard', 'Panel de Control')
            ->assertJsonPath('translated_labels.animals', 'Gestión Ganadera')
            ->assertJsonStructure([
                'formatted_date',
                'formatted_time',
                'formatted_datetime',
                'formatted_currency',
                'formatted_number',
                'locale_info',
                'currency_info',
                'translated_labels',
            ]);
    }

    public function test_set_user_preferences_middleware_inspects_headers_when_guest(): void
    {
        // Test with French headers
        $response = $this->withHeaders([
            'X-User-Locale' => 'fr',
            'X-User-Timezone' => 'Europe/Paris',
            'X-User-Currency' => 'EUR',
        ])->getJson('/api/v1/system/health');

        $response->assertStatus(200)
            ->assertHeader('X-Applied-Locale', 'fr')
            ->assertHeader('X-Applied-Timezone', 'Europe/Paris')
            ->assertHeader('X-Applied-Currency', 'EUR');
    }

    public function test_user_preference_model_format_helpers_and_timezone_conversions(): void
    {
        $preference = new UserPreference([
            'locale' => 'en',
            'timezone' => 'Asia/Karachi', // UTC+5
            'date_format' => 'd M Y',
            'time_format' => '12h',
            'currency' => 'PKR',
            'currency_symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);

        // 12:00:00 UTC corresponds to 17:00:00 in Asia/Karachi (UTC+5)
        $utcTime = Carbon::create(2026, 10, 1, 12, 0, 0, 'UTC');

        $formattedDate = $preference->formatDate($utcTime);
        $this->assertEquals('01 Oct 2026', $formattedDate);

        $formattedTime = $preference->formatTime($utcTime);
        $this->assertEquals('05:00 PM', $formattedTime);

        $formattedDateTime = $preference->formatDateTime($utcTime);
        $this->assertEquals('01 Oct 2026 05:00 PM', $formattedDateTime);

        // Test formatCurrency using Laravel Core Number::currency
        $formattedPkr = $preference->formatCurrency(1250000.00);
        $this->assertNotEmpty($formattedPkr);

        // Test formatNumber using Laravel Core Number::format
        $formattedNum = $preference->formatNumber(45678.90, 2);
        $this->assertNotEmpty($formattedNum);
    }
}
