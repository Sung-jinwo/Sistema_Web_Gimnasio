<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->index('alum_apellido');
            $table->index(['alum_nombre', 'alum_apellido']);
            $table->index('alum_codigo');
            $table->index(['fksede', 'alum_estado']);
            $table->index('created_at');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->index(['fksede', 'created_at']);
            $table->index(['fkusers', 'created_at']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index(['fksede', 'created_at']);
            $table->index('fecha_limite_pago');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->index(['fksede', 'gas_fecha']);
            $table->index('gas_fecha');
        });

        Schema::table('membresias_alumno', function (Blueprint $table) {
            $table->index('estado');
            $table->index(['estado', 'fecha_fin']);
        });

        Schema::table('cajas', function (Blueprint $table) {
            $table->index('estado');
            $table->index(['fksede', 'fecha_apertura']);
            $table->index(['fkuser', 'fecha_apertura']);
        });

        Schema::table('comisiones', function (Blueprint $table) {
            $table->index('estado');
            $table->index(['fkuser', 'created_at']);
            $table->index(['fkcaja', 'created_at']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->index(['fksede', 'prod_cantidad']);
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->index(['fkalum', 'visi_fecha']);
            $table->index(['fksede', 'visi_fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('alumno', function (Blueprint $table) {
            $table->dropIndex(['alum_apellido']);
            $table->dropIndex(['alum_nombre', 'alum_apellido']);
            $table->dropIndex(['alum_codigo']);
            $table->dropIndex(['fksede', 'alum_estado']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['fksede', 'created_at']);
            $table->dropIndex(['fkusers', 'created_at']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(['fksede', 'created_at']);
            $table->dropIndex(['fecha_limite_pago']);
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropIndex(['fksede', 'gas_fecha']);
            $table->dropIndex(['gas_fecha']);
        });

        Schema::table('membresias_alumno', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['estado', 'fecha_fin']);
        });

        Schema::table('cajas', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['fksede', 'fecha_apertura']);
            $table->dropIndex(['fkuser', 'fecha_apertura']);
        });

        Schema::table('comisiones', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['fkuser', 'created_at']);
            $table->dropIndex(['fkcaja', 'created_at']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['fksede', 'prod_cantidad']);
        });

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropIndex(['fkalum', 'visi_fecha']);
            $table->dropIndex(['fksede', 'visi_fecha']);
        });
    }
};
