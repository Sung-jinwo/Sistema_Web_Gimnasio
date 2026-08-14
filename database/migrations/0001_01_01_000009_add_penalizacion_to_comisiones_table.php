<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            $table->decimal('comision_base', 10, 2)->default(0)->after('monto');
            $table->decimal('penalizacion', 10, 2)->default(0)->after('comision_base');
            $table->decimal('comision_final', 10, 2)->default(0)->after('penalizacion');
            $table->date('fecha_acordada_pago')->nullable()->after('comision_final');
            $table->date('fecha_pago_real')->nullable()->after('fecha_acordada_pago');

            $table->index('fecha_acordada_pago');
            $table->index('fecha_pago_real');
        });
    }

    public function down(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            $table->dropIndex(['fecha_acordada_pago']);
            $table->dropIndex(['fecha_pago_real']);
            $table->dropColumn(['comision_base', 'penalizacion', 'comision_final', 'fecha_acordada_pago', 'fecha_pago_real']);
        });
    }
};
