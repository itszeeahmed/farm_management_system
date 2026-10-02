<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseSixApiDocsAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_health_endpoint_returns_operational_status(): void
    {
        $response = $this->getJson('/api/v1/system/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('version', '2.0.0')
            ->assertJsonPath('services.database.status', 'connected')
            ->assertJsonPath('services.cache.status', 'operational')
            ->assertJsonPath('services.storage.status', 'operational')
            ->assertJsonStructure([
                'status',
                'version',
                'framework',
                'timestamp',
                'services' => [
                    'database' => ['status', 'error'],
                    'cache' => ['status'],
                    'storage' => ['status'],
                ],
                'uptime',
            ]);
    }

    public function test_openapi_json_specification_endpoint(): void
    {
        $response = $this->getJson('/api/v1/docs/openapi.json');

        $response->assertStatus(200)
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', 'Farm Management System (v2.0) REST API')
            ->assertJsonPath('info.version', '2.0.0')
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'description', 'version', 'contact', 'license'],
                'servers',
                'tags',
                'paths' => [
                    '/system/health',
                    '/docs/openapi.json',
                    '/auth/login',
                    '/animals',
                    '/animals/{id}',
                    '/milk/records',
                    '/milk/withholding-status/{animalId}',
                    '/collection-centers/intakes',
                    '/feed/formulations/optimize',
                    '/health/cases',
                    '/health/treatments',
                    '/breeding/events',
                    '/meat/feedlots/{id}/gain',
                    '/meat/slaughter/clearance/{animalId}',
                    '/climate/thi-readings',
                    '/sales/customers/{id}/topup',
                    '/sales/delivery-runs/generate',
                    '/finance/chart-of-accounts',
                    '/finance/cost-per-liter',
                    '/finance/biological-valuations/{animalId}/appraise',
                    '/compliance/traceability/forward/{animalId}',
                    '/compliance/traceability/backward/{deliveryStopId}',
                    '/compliance/halal-certifications',
                    '/sync/pull',
                    '/sync/push',
                ],
                'components' => [
                    'securitySchemes' => [
                        'BearerAuth',
                    ],
                    'schemas',
                ],
            ]);

        $tags = array_column($response->json('tags'), 'name');
        $this->assertContains('System Health', $tags);
        $this->assertContains('Livestock & Pedigree', $tags);
        $this->assertContains('Dairy Production & Quality', $tags);
        $this->assertContains('Cooperative Collection Centers', $tags);
        $this->assertContains('Feed, Nutrition & Silage', $tags);
        $this->assertContains('Veterinary Health & Biosecurity', $tags);
        $this->assertContains('Meat, Feedlot & Fleece', $tags);
        $this->assertContains('Financial Accounting & IAS-41', $tags);
        $this->assertContains('Compliance & Traceability', $tags);
        $this->assertContains('IoT Hardware & Mobile Offline Sync', $tags);
    }

    public function test_interactive_swagger_ui_documentation_page(): void
    {
        // 1. Test /docs
        $docsResponse = $this->get('/docs');
        $docsResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('SwaggerUIBundle')
            ->assertSee('Farm Management System — Interactive API Engine')
            ->assertSee('/api/v1/docs/openapi.json');

        // 2. Test /api/documentation
        $apiDocsResponse = $this->get('/api/documentation');
        $apiDocsResponse->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('SwaggerUIBundle');
    }

    public function test_artisan_generate_docs_command(): void
    {
        $this->artisan('api:generate-docs')
            ->expectsOutputToContain('Generating OpenAPI 3.0.3 Specification')
            ->expectsOutputToContain('OpenAPI Specification successfully written')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(public_path('docs/openapi.json')));
        $this->assertGreaterThan(10000, File::size(public_path('docs/openapi.json')));
    }
}
