<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the per-block schedule model with simple fields on the destination:
 * a type (attention/academic), a PDF for academic rooms, free text for offices,
 * and free-text responsible contact. The Schedule and Person entities are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            // Fixed system type: academic (shows a PDF) or attention (shows text
            // schedule + responsible). Defaults to attention.
            $table->string('type')->default('attention')->after('description');
            // Academic rooms: the teacher's schedule PDF (relative storage path).
            $table->string('schedule_file')->nullable()->after('type');
            // Offices/helper rooms: a plain-text schedule, e.g. "Lun–Vie 10:00–19:00".
            $table->string('schedule_text')->nullable()->after('schedule_file');
            // Free-text responsible contact (attention destinations), replaces Person.
            $table->string('responsible_name')->nullable()->after('schedule_text');
            $table->string('responsible_phone')->nullable()->after('responsible_name');
            $table->string('responsible_email')->nullable()->after('responsible_phone');
        });

        Schema::dropIfExists('schedules');
        Schema::dropIfExists('people');
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'schedule_file',
                'schedule_text',
                'responsible_name',
                'responsible_phone',
                'responsible_email',
            ]);
        });

        // The schedules/people tables are not recreated here; restore from their
        // original create migrations if a full rollback of the feature is needed.
    }
};
