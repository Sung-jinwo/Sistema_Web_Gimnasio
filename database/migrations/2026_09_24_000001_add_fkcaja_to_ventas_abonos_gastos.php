<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ventas', 'abonos', 'gastos'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'fkcaja')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->unsignedBigInteger('fkcaja')->nullable()->after('fksede');
                    $table->foreign('fkcaja')->references('id_caja')->on('cajas')->nullOnDelete();
                    $table->index('fkcaja');
                });
            }
        }

        $this->vincularHistoricos();
    }

    public function down(): void
    {
        foreach (['ventas', 'abonos', 'gastos'] as $tabla) {
            if (! Schema::hasColumn($tabla, 'fkcaja')) {
                continue;
            }
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                try {
                    $table->dropForeign([$tabla.'_fkcaja_foreign']);
                } catch (\Throwable) {
                    $table->dropForeign(['fkcaja']);
                }
                $table->dropIndex(['fkcaja']);
                $table->dropColumn('fkcaja');
            });
        }
    }

    /**
     * Vincula registros históricos a una caja solo cuando la coincidencia
     * de usuario, sede y horario es inequívoca (exactamente una candidata).
     * Los ambiguos quedan con fkcaja null para revisión administrativa.
     */
    private function vincularHistoricos(): void
    {
        if (! Schema::hasTable('cajas')) {
            return;
        }

        $config = [
            // tabla => [columna usuario, columna fecha (datetime comparable)]
            'ventas' => ['fkusers', 'created_at'],
            'abonos' => ['fkuser', 'fecha_abono'],
            'gastos' => ['fkuser', 'gas_fecha'],
        ];

        foreach ($config as $tabla => [$colUsuario, $colFecha]) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }

            $pk = match ($tabla) {
                'ventas' => 'id_venta',
                'abonos' => 'id_abono',
                'gastos' => 'id_gasto',
            };

            DB::table($tabla)->whereNull('fkcaja')
                ->select([$pk, 'fksede', $colUsuario.' as fkusuario', $colFecha.' as fecha'])
                ->orderBy($pk)
                ->chunkById(500, function ($filas) use ($tabla, $pk) {
                    foreach ($filas as $fila) {
                        $candidatas = DB::table('cajas')
                            ->where('fksede', $fila->fksede)
                            ->where('fkuser', $fila->fkusuario)
                            ->where('fecha_apertura', '<=', $fila->fecha)
                            ->where(function ($q) use ($fila) {
                                $q->whereNull('fecha_cierre')
                                    ->orWhere('fecha_cierre', '>=', $fila->fecha);
                            })
                            ->pluck('id_caja');

                        if ($candidatas->count() === 1) {
                            DB::table($tabla)->where($pk, $fila->{$pk})
                                ->update(['fkcaja' => $candidatas->first()]);
                        }
                    }
                }, $pk);
        }
    }
};
