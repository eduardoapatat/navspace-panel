<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a photo of the responsible person (attention destinations), shown in
     * the Unity panel. Stored as a relative path on the public disk.
     */
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table): void {
            $table->string('responsible_photo')->nullable()->after('responsible_email');
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table): void {
            $table->dropColumn('responsible_photo');
        });
    }
};
