<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->unsignedBigInteger('fkuser')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('visitas')->whereNull('fkuser')->exists()) {
            throw new RuntimeException('No se puede restaurar visitas.fkuser como obligatorio mientras existan asistencias públicas.');
        }

        Schema::table('visitas', function (Blueprint $table) {
            $table->unsignedBigInteger('fkuser')->nullable(false)->change();
        });
    }
};
