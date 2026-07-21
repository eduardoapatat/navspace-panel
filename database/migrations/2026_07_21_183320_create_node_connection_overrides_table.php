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
        Schema::create('node_connection_overrides', function (Blueprint $table) {
            $table->id();
            // Denormalized on purpose: nodes and destinations also hang off the
            // building directly, and it keeps the API load to a single relation.
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            // The pair is always stored normalized (lowest id first), so A-B and
            // B-A can never be duplicated. Deleting either node drops the rule.
            $table->foreignId('node_a_id')->constrained('nodes')->cascadeOnDelete();
            $table->foreignId('node_b_id')->constrained('nodes')->cascadeOnDelete();
            // ConnectionOverrideType: 'blocked' (never link) or 'forced' (always).
            $table->string('type');
            $table->timestamps();

            $table->unique(['node_a_id', 'node_b_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_connection_overrides');
    }
};
