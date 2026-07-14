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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            // Relative public-disk paths (e.g. "buildings/x.jpg", "models/x.glb").
            // Absolute URLs are derived at read time so they follow the request's
            // host, working over both the local .test domain and a LAN IP.
            $table->string('image_path')->nullable();
            $table->string('model_path')->nullable();
            // Point Graph parameters the Unity app uses to build the A* graph.
            $table->json('pathfinding_config')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
