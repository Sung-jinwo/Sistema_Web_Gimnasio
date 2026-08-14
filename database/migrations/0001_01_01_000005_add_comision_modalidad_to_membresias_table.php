<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            $table->decimal('comision', 5, 2)->default(0)->after('mem_precio');
            $table->enum('modalidad', ['por_meses', 'por_fechas'])->default('por_meses')->after('comision');
        });
    }

    public function down(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            $table->dropColumn(['comision', 'modalidad']);
        });
    }
};
