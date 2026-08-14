<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente')->after('gas_observacion');
            $table->unsignedBigInteger('aprobado_por')->nullable()->after('estado');
            $table->datetime('fecha_aprobacion')->nullable()->after('aprobado_por');
            $table->text('motivo_rechazo')->nullable()->after('fecha_aprobacion');

            $table->foreign('aprobado_por')->references('id')->on('users')->nullOnDelete();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['aprobado_por']);
            $table->dropIndex(['estado']);
            $table->dropColumn(['estado', 'aprobado_por', 'fecha_aprobacion', 'motivo_rechazo']);
        });
    }
};
