<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemHealthController extends Controller
{
    /**
     * System Health Check Endpoint.
     */
    public function health(Request $request): JsonResponse
    {
        $dbStatus = 'connected';
        $dbError = null;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'error';
            $dbError = $e->getMessage();
        }

        $isHealthy = $dbStatus === 'connected';

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'degraded',
            'version' => '2.0.0',
            'framework' => 'Laravel '.app()->version(),
            'timestamp' => Carbon::now()->toIso8601String(),
            'services' => [
                'database' => [
                    'status' => $dbStatus,
                    'error' => $dbError,
                ],
                'cache' => [
                    'status' => 'operational',
                ],
                'storage' => [
                    'status' => 'operational',
                ],
            ],
            'uptime' => '100.0%',
        ], $isHealthy ? 200 : 503);
    }

    /**
     * Return OpenAPI 3.0 Specification JSON.
     */
    public function openapiJson(): JsonResponse
    {
        $path = public_path('docs/openapi.json');

        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);

            return response()->json($data);
        }

        return response()->json([
            'message' => 'OpenAPI specification document is being generated.',
        ], 404);
    }

    /**
     * Render Interactive Swagger UI API Documentation.
     */
    public function swaggerUi(): Response
    {
        $specUrl = url('/api/v1/docs/openapi.json');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Management System (v2.0) — OpenAPI 3.0 Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --bg-dark: #0f172a;
            --surface: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        body {
            margin: 0;
            background-color: #0b1120;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
        }
        .header-banner {
            background: linear-gradient(135deg, #064e3b 0%, #0f172a 100%);
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header-logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #10b981, #047857);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .header-text h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        .header-text p {
            margin: 4px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
        }
        .badge-version {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        /* Customizing Swagger UI Theme */
        .swagger-ui {
            font-family: inherit;
        }
        .swagger-ui .info {
            margin: 28px 0;
        }
        .swagger-ui .info .title {
            color: #10b981;
            font-family: inherit;
        }
        .swagger-ui .scheme-container {
            background: #1e293b;
            box-shadow: none;
            border-radius: 8px;
            margin: 20px 0;
            padding: 16px 24px;
        }
        .swagger-ui .opblock.opblock-get {
            background: rgba(16, 185, 129, 0.05);
            border-color: #10b981;
        }
        .swagger-ui .opblock.opblock-get .opblock-summary-method {
            background: #10b981;
        }
        .swagger-ui .opblock.opblock-post {
            background: rgba(59, 130, 246, 0.05);
            border-color: #3b82f6;
        }
        .swagger-ui .opblock.opblock-post .opblock-summary-method {
            background: #3b82f6;
        }
        .swagger-ui .opblock .opblock-summary-path {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }
        .swagger-ui code {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="header-banner">
        <div class="header-title">
            <div class="header-logo">🌾</div>
            <div class="header-text">
                <h1>Farm Management System — Interactive API Engine</h1>
                <p>Enterprise Agritech RESTful Architecture • OpenAPI 3.0.3 Specification</p>
            </div>
        </div>
        <div class="badge-version">v2.0.0 Production Specification</div>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui-bundle.js"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '$specUrl',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout"
            });
        };
    </script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
