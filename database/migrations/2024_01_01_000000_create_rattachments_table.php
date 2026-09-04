<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rattachments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('rattachable_type');
            $table->string('rattachable_id');
            $table->string('target_type');
            $table->string('target_id');

            $table->string('role');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['rattachable_type', 'rattachable_id']);
            $table->index(['target_type', 'target_id']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rattachments');
    }
};
