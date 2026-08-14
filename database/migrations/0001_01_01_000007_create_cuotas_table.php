<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuotas', function (Blueprint $table) {
            $table->bigIncrements('id_cuota');
            $table->unsignedBigInteger('fkventa')->nullable();
            $table->unsignedBigInteger('fkpago')->nullable();
            $table->integer('numero_cuota');
            $table->decimal('monto', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('saldo', 10, 2);
            $table->date('fecha_acordada');
            $table->date('fecha_pago_real')->nullable();
            $table->enum('estado', ['pendiente', 'parcial', 'pagada', 'vencida'])->default('pendiente');
            $table->timestamps();

            $table->foreign('fkventa')->references('id_venta')->on('ventas')->onDelete('cascade');
            $table->foreign('fkpago')->references('id_pag')->on('pagos')->onDelete('cascade');

            $table->index(['fkventa', 'estado']);
            $table->index(['fkpago', 'estado']);
            $table->index('fecha_acordada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
    }
};
