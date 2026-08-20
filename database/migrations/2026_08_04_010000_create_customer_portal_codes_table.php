<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('customer_portal_codes')) {
            Schema::create('customer_portal_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('code_hash');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('expires_at')->index();
                $table->timestamp('consumed_at')->nullable();
                $table->string('request_ip', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_codes');
    }
};
