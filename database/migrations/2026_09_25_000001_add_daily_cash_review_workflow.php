<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->boolean('es_efectivo')->default(false)->after('metod_nombre');
        });

        DB::table('metodos_pago')
            ->whereRaw('LOWER(metod_nombre) = ?', ['efectivo'])
            ->update(['es_efectivo' => true]);

        Schema::table('gastos', function (Blueprint $table) {
            $table->unsignedBigInteger('fkmetodo')->nullable()->after('fkcategoria');
            $table->foreign('fkmetodo')->references('id_metod')->on('metodos_pago')->nullOnDelete();
        });

        Schema::table('cajas', function (Blueprint $table) {
            $table->date('fecha_operativa')->nullable()->after('fecha_apertura');
            $table->dateTime('enviado_cierre_at')->nullable()->after('fecha_cierre');
            $table->dateTime('revisada_at')->nullable()->after('enviado_cierre_at');
            $table->unsignedBigInteger('revisada_por')->nullable()->after('revisada_at');
            $table->text('observacion_revision')->nullable()->after('observacion');
            $table->foreign('revisada_por')->references('id')->on('users')->nullOnDelete();
            $table->index(['fkuser', 'fecha_operativa']);
            $table->enum('estado', [
                'abierta', 'pendiente_cierre', 'pendiente_revision', 'observada', 'cerrada', 'anulada',
            ])->default('abierta')->change();
        });

        DB::table('cajas')->orderBy('id_caja')->each(function ($caja) {
            $fecha = Carbon::parse($caja->fecha_apertura, config('app.timezone'))->toDateString();
            $estado = $caja->estado;
            if ($estado === 'abierta' && $fecha < today()->toDateString()) {
                $estado = 'pendiente_cierre';
            }

            DB::table('cajas')->where('id_caja', $caja->id_caja)->update([
                'fecha_operativa' => $fecha,
                'estado' => $estado,
            ]);
        });

        Schema::create('caja_cierre_detalles', function (Blueprint $table) {
            $table->id('id_detalle_cierre');
            $table->unsignedBigInteger('fkcaja');
            $table->unsignedBigInteger('fkmetodo');
            $table->decimal('monto_esperado', 10, 2)->default(0);
            $table->decimal('monto_declarado', 10, 2)->default(0);
            $table->decimal('diferencia', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['fkcaja', 'fkmetodo']);
            $table->foreign('fkcaja')->references('id_caja')->on('cajas')->cascadeOnDelete();
            $table->foreign('fkmetodo')->references('id_metod')->on('metodos_pago');
        });
    }

    public function down(): void
    {
        $tieneDatosNuevos = Schema::hasTable('caja_cierre_detalles')
            && DB::table('caja_cierre_detalles')->exists();
        $tieneEstadosNuevos = DB::table('cajas')
            ->whereIn('estado', ['pendiente_cierre', 'pendiente_revision', 'observada'])
            ->exists();

        if ($tieneDatosNuevos || $tieneEstadosNuevos) {
            throw new RuntimeException('No se puede revertir el flujo de cierre diario porque existen conciliaciones que se perderían.');
        }

        Schema::dropIfExists('caja_cierre_detalles');

        Schema::table('cajas', function (Blueprint $table) {
            $table->dropForeign(['revisada_por']);
            $table->dropIndex(['fkuser', 'fecha_operativa']);
            $table->dropColumn([
                'fecha_operativa', 'enviado_cierre_at', 'revisada_at', 'revisada_por', 'observacion_revision',
            ]);
            $table->enum('estado', ['abierta', 'cerrada', 'anulada'])->default('abierta')->change();
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['fkmetodo']);
            $table->dropColumn('fkmetodo');
        });

        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropColumn('es_efectivo');
        });
    }
};
