<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('url_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('expired_at');
            $table->string('usage_count');
            $table->string('max_usage_limit');
            $table->string('data');
            $table->string('type');
            $table->string('tokenable');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('url_tokens');
    }
};
