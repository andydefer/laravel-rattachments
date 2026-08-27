<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rattachments', function (Blueprint $table) {
            $table->id();

            $table->morphs('rattachable');
            $table->morphs('target');

            $table->string('role')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['rattachable_type', 'rattachable_id', 'target_type', 'target_id'], 'rattachments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rattachments');
    }
};
