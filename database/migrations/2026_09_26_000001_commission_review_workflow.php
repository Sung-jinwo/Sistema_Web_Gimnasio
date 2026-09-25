<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public const ESTADOS = [
        'esperando_pago',
        'pendiente_revision',
        'aprobada',
        'observada',
        'liquidada',
        'anulada',
    ];

    public function up(): void
    {
        Schema::table('comisiones', function (Blueprint $table) {
            if (! Schema::hasColumn('comisiones', 'aprobada_por')) {
                $table->unsignedBigInteger('aprobada_por')->nullable()->after('fkcaja');
                $table->foreign('aprobada_por')->references('id')->on('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('comisiones', 'fecha_aprobacion')) {
                $table->dateTime('fecha_aprobacion')->nullable()->after('aprobada_por');
            }
            if (! Schema::hasColumn('comisiones', 'fecha_habilitacion')) {
                $table->dateTime('fecha_habilitacion')->nullable()->after('fecha_aprobacion');
            }
            if (! Schema::hasColumn('comisiones', 'motivo_observacion')) {
                $table->text('motivo_observacion')->nullable()->after('fecha_habilitacion');
            }
        });

        Schema::table('liquidaciones_comision', function (Blueprint $table) {
            if (! Schema::hasColumn('liquidaciones_comision', 'fkmetodo')) {
                $table->unsignedBigInteger('fkmetodo')->nullable()->after('total');
                $table->foreign('fkmetodo')->references('id_metod')->on('metodos_pago')->nullOnDelete();
            }
            if (! Schema::hasColumn('liquidaciones_comision', 'referencia')) {
                $table->string('referencia', 100)->nullable()->after('fkmetodo');
            }
            if (! Schema::hasColumn('liquidaciones_comision', 'observacion')) {
                $table->text('observacion')->nullable()->after('referencia');
            }
        });

        // El enum se amplía primero a un superconjunto (estricto no aceptaría
        // valores nuevos con el enum viejo ni viceversa), se mapean los datos
        // y recién después se restringe al conjunto final.
        Schema::table('comisiones', function (Blueprint $table) {
            $table->enum('estado', array_values(array_unique(array_merge(['pendiente', 'liquidada'], self::ESTADOS))))
                ->default('esperando_pago')->change();
        });

        $this->migrarEstadosHistoricos();

        Schema::table('comisiones', function (Blueprint $table) {
            $table->enum('estado', self::ESTADOS)->default('esperando_pago')->change();
        });
    }

    public function down(): void
    {
        $tieneRevisiones = DB::table('comisiones')
            ->whereNotNull('aprobada_por')
            ->orWhereNotNull('motivo_observacion')
            ->orWhereNotNull('fecha_habilitacion')
            ->exists();
        $tieneLiquidacionesNuevas = Schema::hasColumn('liquidaciones_comision', 'fkmetodo')
            && DB::table('liquidaciones_comision')->whereNotNull('fkmetodo')->exists();
        $tieneEstadosNuevos = DB::table('comisiones')
            ->whereIn('estado', ['esperando_pago', 'pendiente_revision', 'aprobada', 'observada', 'anulada'])
            ->exists();

        if ($tieneRevisiones || $tieneLiquidacionesNuevas || $tieneEstadosNuevos) {
            throw new RuntimeException('No se puede revertir el flujo de revisión de comisiones porque existen aprobaciones, observaciones o liquidaciones nuevas que se perderían.');
        }

        Schema::table('liquidaciones_comision', function (Blueprint $table) {
            $table->dropForeign(['fkmetodo']);
            $table->dropColumn(['fkmetodo', 'referencia', 'observacion']);
        });

        Schema::table('comisiones', function (Blueprint $table) {
            $table->dropForeign(['aprobada_por']);
            $table->dropColumn(['aprobada_por', 'fecha_aprobacion', 'fecha_habilitacion', 'motivo_observacion']);
            $table->enum('estado', ['pendiente', 'liquidada'])->default('pendiente')->change();
        });
    }

    /**
     * Las pendientes con venta incompleta esperan el pago; las pagadas
     * quedan listas para revisión. Liquidadas y el resto se conservan.
     */
    private function migrarEstadosHistoricos(): void
    {
        DB::table('comisiones')->where('estado', 'pendiente')->orderBy('id_comision')->chunkById(500, function ($comisiones) {
            foreach ($comisiones as $comision) {
                $saldo = $comision->fkventa
                    ? (float) DB::table('ventas')->where('id_venta', $comision->fkventa)->value('saldo')
                    : 0;

                if ($saldo > 0) {
                    DB::table('comisiones')->where('id_comision', $comision->id_comision)
                        ->update(['estado' => 'esperando_pago']);
                } else {
                    DB::table('comisiones')->where('id_comision', $comision->id_comision)
                        ->update([
                            'estado' => 'pendiente_revision',
                            'fecha_habilitacion' => $comision->updated_at,
                        ]);
                }
            }
        }, 'id_comision');
    }
};
