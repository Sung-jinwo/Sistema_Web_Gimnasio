<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('membresias', 'permite_congelamiento')) {
            Schema::table('membresias', function (Blueprint $table) {
                $table->boolean('permite_congelamiento')->default(false)->after('estado');
            });
        }

        if (! Schema::hasTable('membresia_congelamientos')) {
            Schema::create('membresia_congelamientos', function (Blueprint $table) {
                $table->id('id_congelamiento');
                $table->unsignedBigInteger('fkmembresia_alumno');
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->unsignedInteger('dias');
                $table->text('motivo');
                $table->enum('estado', ['programado', 'activo', 'finalizado', 'cancelado'])->default('programado');
                $table->unsignedBigInteger('fkuser');
                $table->timestamps();

                $table->foreign('fkmembresia_alumno')->references('id_membresia_alumno')->on('membresias_alumno')->cascadeOnDelete();
                $table->foreign('fkuser')->references('id')->on('users');
                $table->index(['fkmembresia_alumno', 'estado']);
            });
        }

        if (! Schema::hasTable('membresia_ajustes_vigencia')) {
            Schema::create('membresia_ajustes_vigencia', function (Blueprint $table) {
                $table->id('id_ajuste');
                $table->unsignedBigInteger('fkmembresia_alumno');
                $table->date('fecha_anterior');
                $table->date('fecha_nueva');
                $table->integer('diferencia_dias');
                $table->text('motivo');
                $table->unsignedBigInteger('fkadmin');
                $table->timestamps();

                $table->foreign('fkmembresia_alumno')->references('id_membresia_alumno')->on('membresias_alumno')->cascadeOnDelete();
                $table->foreign('fkadmin')->references('id')->on('users');
                $table->index('fkmembresia_alumno');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('membresia_congelamientos') && DB::table('membresia_congelamientos')->exists()) {
            throw new RuntimeException('No se puede revertir porque existen congelamientos que perderían trazabilidad.');
        }

        if (Schema::hasTable('membresia_ajustes_vigencia') && DB::table('membresia_ajustes_vigencia')->exists()) {
            throw new RuntimeException('No se puede revertir porque existen ajustes de vigencia que perderían trazabilidad.');
        }

        Schema::dropIfExists('membresia_ajustes_vigencia');
        Schema::dropIfExists('membresia_congelamientos');

        if (Schema::hasColumn('membresias', 'permite_congelamiento')) {
            Schema::table('membresias', function (Blueprint $table) {
                $table->dropColumn('permite_congelamiento');
            });
        }
    }
};
