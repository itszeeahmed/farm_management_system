<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_user_can_login_and_receive_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@farm.local',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role'],
                'preferences' => ['locale', 'currency', 'timezone', 'date_format', 'time_format'],
                'farms',
                'active_farm',
            ]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@farm.local',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_profile_and_farms(): void
    {
        $user = User::where('email', 'owner@farm.local')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'owner@farm.local');
    }

    public function test_farms_endpoint_returns_multi_tenant_farms(): void
    {
        $response = $this->getJson('/api/v1/farms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'type',
                        'organization_id',
                        'animals_count',
                    ],
                ],
            ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'owner@farm.local')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);
    }
}
