<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create a Super Admin User
        User::updateOrCreate(
            ['email' => 'superadmin@droom.in'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'mobile' => '9999999991',
                'role' => 'SUPER_ADMIN',
                'device_id' => Str::uuid(),
                'device_type' => 'desktop',
                'os_name' => 'Linux',
                'app_version' => '1.0.0',
                'ip_address' => '127.0.0.1',
                'lat' => 28.4595,
                'long' => 77.0266,
                'browser_name' => 'Chrome',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create a Partner Admin User
        User::updateOrCreate(
            ['email' => 'partneradmin@droom.in'],
            [
                'name' => 'Partner Admin',
                'password' => Hash::make('PartnerAdmin@123'),
                'mobile' => '9999999992',
                'role' => 'PARTNER_ADMIN',
                'device_id' => Str::uuid(),
                'device_type' => 'desktop',
                'os_name' => 'Windows',
                'app_version' => '1.0.0',
                'ip_address' => '192.168.1.1',
                'lat' => 28.4595,
                'long' => 77.0266,
                'browser_name' => 'Firefox',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 3. Create a Partner Agent User
        User::updateOrCreate(
            ['email' => 'partneragent@droom.in'],
            [
                'name' => 'Partner Agent',
                'password' => Hash::make('PartnerAgent@123'),
                'mobile' => '9999999993',
                'role' => 'PARTNER_AGENT',
                'device_id' => Str::uuid(),
                'device_type' => 'mobile',
                'os_name' => 'Android',
                'app_version' => '1.2.0',
                'ip_address' => '10.0.0.2',
                'lat' => 28.5355,
                'long' => 77.3910,
                'browser_name' => 'Chrome Mobile',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 4. Create an Insurer User
        User::updateOrCreate(
            ['email' => 'insurer@droom.in'],
            [
                'name' => 'Insurer User',
                'password' => Hash::make('Insurer@123'),
                'mobile' => '9999999994',
                'role' => 'INSURER',
                'device_id' => Str::uuid(),
                'device_type' => 'desktop',
                'os_name' => 'macOS',
                'app_version' => '1.0.0',
                'ip_address' => '172.16.0.5',
                'lat' => 19.0760,
                'long' => 72.8777,
                'browser_name' => 'Safari',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 5. Create Partner linked to Partner Admin User
        $partnerAdmin = User::where('email', 'partneradmin@droom.in')->first();
        if ($partnerAdmin) {
            $partner = \App\Models\Partner::updateOrCreate(
                ['user_id' => $partnerAdmin->id],
                [
                    'business_name' => 'Droom Test Partner',
                    'partner_type' => 'dealer',
                    'contact_email' => 'partneradmin@droom.in',
                    'contact_person_name' => 'Partner Admin',
                    'contact_person_mobile' => '9999999992',
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            // 6. Create Partner Credentials
            \App\Models\PartnerCredential::updateOrCreate(
                ['partner_id' => $partner->id, 'api_key' => 'test_api_key_123'],
                [
                    'api_secret_hash' => Hash::make('test_api_secret_123'),
                    'status' => 'active',
                    'rate_limit_tier' => 'standard',
                ]
            );
        }
    }
}
