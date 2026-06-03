<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartnerCredentialApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_create_a_credential(): void
    {
        $user = User::factory()->create();

        $partner = Partner::query()->create([
            'user_id' => $user->id,
            'business_name' => 'Droom Partner',
            'partner_type' => 'AGENT',
            'contact_email' => 'partner@example.com',
            'contact_person_name' => 'Partner Admin',
            'contact_person_mobile' => '9999999999',
            'status' => 'active',
        ]);

        $existingCredential = PartnerCredential::query()->create([
            'partner_id' => $partner->id,
            'api_key' => 'existing-key',
            'api_secret_hash' => Hash::make('existing-secret'),
            'status' => 'active',
            'rate_limit_tier' => 'standard',
        ]);

        $response = $this->postJson('/api/partner-credentials', [
            'partner_id' => $partner->id,
            'api_key' => 'new-key-123',
            'api_secret' => 'new-secret-123',
            'status' => 'active',
            'rate_limit_tier' => 'premium',
        ], [
            'X-Client-Id' => $existingCredential->api_key,
            'X-Client-Secret' => 'existing-secret',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Partner credential created successfully.')
            ->assertJsonPath('data.partner_id', $partner->id)
            ->assertJsonPath('data.api_key', 'new-key-123')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.rate_limit_tier', 'premium');

        $this->assertDatabaseHas('partner_credentials', [
            'partner_id' => $partner->id,
            'api_key' => 'new-key-123',
            'status' => 'active',
            'rate_limit_tier' => 'premium',
        ]);

        $storedCredential = PartnerCredential::query()->where('api_key', 'new-key-123')->firstOrFail();
        $this->assertTrue(Hash::check('new-secret-123', $storedCredential->api_secret_hash));
    }
}