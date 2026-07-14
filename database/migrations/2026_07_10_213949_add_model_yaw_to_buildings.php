<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Yaw correction (degrees) applied to the model in Unity to fix the known
     * 180° flip of Blender-exported glb. Auto-detected from the glb generator on
     * upload; can be overridden from the editor for other exporters. 0 = none.
     */
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table): void {
            $table->smallInteger('model_yaw')->default(0)->after('model_path');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table): void {
            $table->dropColumn('model_yaw');
        });
    }
};
