<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fileponds', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('filepath');
            $table->string('extension');
            $table->string('mimetypes');
            $table->string('disk');
            $table->string('filesize');
            $table->string('expires_at');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fileponds');
    }
};
