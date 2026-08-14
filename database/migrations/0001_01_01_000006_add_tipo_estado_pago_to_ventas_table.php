<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('tipo_venta', ['membresia', 'producto', 'rapida'])->default('producto')->after('fkalum');
            $table->enum('estado_pago', ['pagado', 'parcial', 'pendiente', 'vencido'])->default('pagado')->after('estado_venta');
            $table->decimal('monto_pagado', 10, 2)->default(0)->after('estado_pago');
            $table->decimal('saldo', 10, 2)->default(0)->after('monto_pagado');
            $table->date('fecha_acordada')->nullable()->after('saldo');

            $table->index('tipo_venta');
            $table->index('estado_pago');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['tipo_venta']);
            $table->dropIndex(['estado_pago']);
            $table->dropColumn(['tipo_venta', 'estado_pago', 'monto_pagado', 'saldo', 'fecha_acordada']);
        });
    }
};
