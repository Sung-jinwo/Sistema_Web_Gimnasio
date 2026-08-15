<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categorias', 'cat_estado')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->boolean('cat_estado')->default(true)->after('cat_nombre');
            });
        }

        if (! Schema::hasColumn('productos', 'prod_estado')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->boolean('prod_estado')->default(true)->after('prod_stock_minimo');
            });
        }

        if (! Schema::hasColumn('membresias', 'fecha_inicio_fija')) {
            Schema::table('membresias', function (Blueprint $table) {
                $table->date('fecha_inicio_fija')->nullable()->after('modalidad');
                $table->date('fecha_fin_fija')->nullable()->after('fecha_inicio_fija');
            });
        }

        if (! Schema::hasColumn('ventas', 'motivo_anulacion')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->text('motivo_anulacion')->nullable()->after('observacion');
                $table->foreignId('anulada_por')->nullable()->after('motivo_anulacion')->constrained('users')->nullOnDelete();
                $table->timestamp('anulada_at')->nullable()->after('anulada_por');
            });
        }

        if (! Schema::hasTable('liquidaciones_comision')) {
            Schema::create('liquidaciones_comision', function (Blueprint $table) {
                $table->id('id_liquidacion');
                $table->foreignId('fkuser')->constrained('users');
                $table->foreignId('liquidada_por')->constrained('users');
                $table->decimal('total', 10, 2);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('liquidacion_comision_detalles')) {
            Schema::create('liquidacion_comision_detalles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fkliquidacion')->constrained('liquidaciones_comision', 'id_liquidacion')->cascadeOnDelete();
                $table->unsignedBigInteger('fkcomision');
                $table->foreign('fkcomision')->references('id_comision')->on('comisiones');
            });
        }
    }

    public function down(): void
    {
        // Migración correctiva: no elimina columnas que pudieran existir previamente.
    }
};
