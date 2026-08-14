<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membresias_alumno', function (Blueprint $table) {
            $table->bigIncrements('id_membresia_alumno');
            $table->unsignedBigInteger('fkalumno');
            $table->unsignedBigInteger('fkmem');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('precio_vendido', 10, 2);
            $table->decimal('comision_aplicada', 10, 2)->default(0);
            $table->enum('modalidad', ['por_meses', 'por_fechas'])->default('por_meses');
            $table->enum('estado', ['activa', 'vencida', 'cancelada'])->default('activa');
            $table->timestamps();

            $table->foreign('fkalumno')->references('id_alumno')->on('alumno')->onDelete('cascade');
            $table->foreign('fkmem')->references('id_mem')->on('membresias')->onDelete('restrict');

            $table->index(['fkalumno', 'estado']);
            $table->index('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membresias_alumno');
    }
};
