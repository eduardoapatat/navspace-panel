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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->float('position_x');
            $table->float('position_y');
            $table->float('position_z');
            $table->boolean('is_initial')->default(false);
            // Facing direction (degrees, 0-360) of the initial node: which way the
            // user looks when calibrated here. Only meaningful for the is_initial
            // node; null otherwise. Unity uses it to orient the model.
            $table->float('heading')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
