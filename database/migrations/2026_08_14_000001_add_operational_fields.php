<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categorias')->select('cat_nombre')->groupBy('cat_nombre')->havingRaw('COUNT(*) > 1')->pluck('cat_nombre')->each(function ($nombre) {
            $ids = DB::table('categorias')->where('cat_nombre', $nombre)->orderBy('id_categoria')->pluck('id_categoria');
            $conservar = $ids->shift();
            DB::table('productos')->whereIn('fkcategoria', $ids)->update(['fkcategoria' => $conservar]);
            DB::table('categorias')->whereIn('id_categoria', $ids)->delete();
        });
        Schema::table('membresias', function (Blueprint $table) {
            $table->date('fecha_inicio_fija')->nullable()->after('modalidad');
            $table->date('fecha_fin_fija')->nullable()->after('fecha_inicio_fija');
            $table->unsignedTinyInteger('mem_duracion')->nullable()->change();
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->boolean('cat_estado')->default(true)->after('cat_nombre');
            $table->unique('cat_nombre');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('prod_estado')->default(true)->after('prod_stock_minimo');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->string('estado_venta', 20)->default('completado')->change();
            $table->text('motivo_anulacion')->nullable()->after('observacion');
            $table->foreignId('anulada_por')->nullable()->after('motivo_anulacion')->constrained('users')->nullOnDelete();
            $table->timestamp('anulada_at')->nullable()->after('anulada_por');
        });

        Schema::create('liquidaciones_comision', function (Blueprint $table) {
            $table->id('id_liquidacion');
            $table->foreignId('fkuser')->constrained('users');
            $table->foreignId('liquidada_por')->constrained('users');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
        Schema::create('liquidacion_comision_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fkliquidacion')->constrained('liquidaciones_comision', 'id_liquidacion')->cascadeOnDelete();
            $table->unsignedBigInteger('fkcomision');
            $table->foreign('fkcomision')->references('id_comision')->on('comisiones');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidacion_comision_detalles');
        Schema::dropIfExists('liquidaciones_comision');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropColumn(['motivo_anulacion', 'anulada_at']);
        });
        Schema::table('productos', fn (Blueprint $table) => $table->dropColumn('prod_estado'));
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['cat_nombre']);
            $table->dropColumn('cat_estado');
        });
        Schema::table('membresias', fn (Blueprint $table) => $table->dropColumn(['fecha_inicio_fija', 'fecha_fin_fija']));
    }
};
