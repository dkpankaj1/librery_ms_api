<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('library_id')->nullable()->after('id')->index();
            $table->string('user_type', 20)->default('library_user')->after('email'); // super_admin | library_user
            $table->string('phone', 20)->nullable()->after('user_type');
            $table->string('account_status', 20)->default('active')->after('phone'); // active | inactive | suspended
            $table->timestamp('account_valid_until')->nullable()->after('account_status');
            $table->timestamp('account_verified_at')->nullable()->after('account_valid_until');
            $table->timestamp('last_login_at')->nullable()->after('account_verified_at');
            $table->boolean('is_active')->default(true)->after('last_login_at');
            $table->string('avatar')->nullable()->after('is_active');
            $table->text('notes')->nullable()->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'library_id',
                'user_type',
                'phone',
                'account_status',
                'account_valid_until',
                'account_verified_at',
                'last_login_at',
                'is_active',
                'avatar',
                'notes',
            ]);
        });
    }
};
