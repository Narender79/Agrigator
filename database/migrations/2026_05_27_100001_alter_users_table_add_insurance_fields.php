<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 15)->unique()->after('email');
            $table->enum('role', ['SUPER_ADMIN', 'PARTNER_ADMIN', 'PARTNER_AGENT', 'INSURER'])->default('PARTNER_AGENT')->after('password');
            $table->uuid('device_id')->nullable()->after('role');
            $table->string('device_type', 50)->nullable()->after('device_id'); // mobile, tablet, desktop
            $table->string('os_name', 50)->nullable()->after('device_type');   // Android, iOS, Windows
            $table->string('app_version', 20)->nullable()->after('os_name');
            $table->string('ip_address', 45)->nullable()->after('app_version');
            $table->decimal('lat', 10, 8)->nullable()->after('ip_address');
            $table->decimal('long', 11, 8)->nullable()->after('lat');
            $table->string('browser_name', 100)->nullable()->after('long');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('browser_name');

            $table->unsignedBigInteger('created_by')->nullable()->after('remember_token');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile',
                'role',
                'device_id',
                'device_type',
                'os_name',
                'app_version',
                'ip_address',
                'lat',
                'long',
                'browser_name',
                'status',
                'created_by',
                'updated_by'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
