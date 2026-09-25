<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ventas', 'legacy_pago_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->unsignedBigInteger('legacy_pago_id')->nullable()->unique()->after('id_venta');
            });
        }

        if (! Schema::hasColumn('membresias_alumno', 'fkventa')) {
            Schema::table('membresias_alumno', function (Blueprint $table) {
                $table->unsignedBigInteger('fkventa')->nullable()->unique()->after('id_membresia_alumno');
                $table->foreign('fkventa')->references('id_venta')->on('ventas')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('abonos')) {
            Schema::create('abonos', function (Blueprint $table) {
                $table->id('id_abono');
                $table->unsignedBigInteger('fkventa');
                $table->unsignedBigInteger('fkcuota')->nullable();
                $table->unsignedBigInteger('fkmetodo');
                $table->unsignedBigInteger('fksede');
                $table->unsignedBigInteger('fkuser');
                $table->decimal('monto', 10, 2);
                $table->dateTime('fecha_abono');
                $table->string('num_comprobante', 50)->nullable();
                $table->text('observacion')->nullable();
                $table->unsignedBigInteger('legacy_pago_id')->nullable()->unique();
                $table->timestamps();

                $table->foreign('fkventa')->references('id_venta')->on('ventas')->cascadeOnDelete();
                $table->foreign('fkcuota')->references('id_cuota')->on('cuotas')->nullOnDelete();
                $table->foreign('fkmetodo')->references('id_metod')->on('metodos_pago');
                $table->foreign('fksede')->references('id_sede')->on('sedes');
                $table->foreign('fkuser')->references('id')->on('users');
                $table->index(['fksede', 'fecha_abono']);
            });
        }

        $this->migrarPagosExistentes();
    }

    private function migrarPagosExistentes(): void
    {
        DB::transaction(function () {
            DB::table('pagos')->orderBy('id_pag')->get()->each(function ($pago) {
                $total = (float) (($pago->total ?? 0) > 0 ? $pago->total : ($pago->pag_monto ?? 0));
                $montoRegistrado = (float) ($pago->monto_pagado ?? 0);
                if ($pago->estado_pago === 'completo' && $montoRegistrado <= 0) {
                    $montoRegistrado = $total;
                }
                $pagado = min($total, max(0, $montoRegistrado));
                $saldo = max(0, $total - $pagado);
                $estadoPago = $pago->estado_pago === 'reservado'
                    ? 'pendiente'
                    : ($saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente'));

                $ventaId = DB::table('ventas')->where('legacy_pago_id', $pago->id_pag)->value('id_venta');

                if (! $ventaId) {
                    $ventaId = DB::table('ventas')->insertGetId([
                        'legacy_pago_id' => $pago->id_pag,
                        'fkalum' => $pago->fkalum,
                        'fkusers' => $pago->fkuser,
                        'fksede' => $pago->fksede,
                        'fkmetodo' => $pago->fkmetodo,
                        'fkproducto' => null,
                        'tipo_venta' => 'membresia',
                        'venta_fecha' => Carbon::parse($pago->created_at)->toDateString(),
                        'estado_venta' => $pago->estado_pago === 'reservado' ? 'reservado' : 'completado',
                        'estado_pago' => $estadoPago,
                        'venta_total' => $total,
                        'venta_descuento' => (float) ($pago->pag_descuento ?? 0),
                        'monto_pagado' => $pagado,
                        'saldo' => $saldo,
                        'fecha_acordada' => $saldo > 0 ? $pago->fecha_limite_pago : null,
                        'observacion' => $pago->observacion,
                        'created_at' => $pago->created_at,
                        'updated_at' => $pago->updated_at,
                    ], 'id_venta');
                }

                $membresia = DB::table('membresias')->where('id_mem', $pago->fkmem)->first();
                $asignacion = DB::table('membresias_alumno')
                    ->where('fkalumno', $pago->fkalum)
                    ->where('fkmem', $pago->fkmem)
                    ->where('fecha_inicio', $pago->pag_inicio)
                    ->where('fecha_fin', $pago->pag_fin)
                    ->first();

                if ($asignacion && (! $asignacion->fkventa || (int) $asignacion->fkventa === (int) $ventaId)) {
                    DB::table('membresias_alumno')->where('id_membresia_alumno', $asignacion->id_membresia_alumno)
                        ->update(['fkventa' => $ventaId]);
                } elseif (! $asignacion || $asignacion->fkventa) {
                    DB::table('membresias_alumno')->insert([
                        'fkventa' => $ventaId,
                        'fkalumno' => $pago->fkalum,
                        'fkmem' => $pago->fkmem,
                        'fecha_inicio' => $pago->pag_inicio,
                        'fecha_fin' => $pago->pag_fin,
                        'precio_vendido' => $total,
                        'comision_aplicada' => (float) ($membresia->comision ?? 0),
                        'modalidad' => $membresia->modalidad ?? 'por_meses',
                        'estado' => Carbon::parse($pago->pag_fin)->lt(today()) ? 'vencida' : 'activa',
                        'created_at' => $pago->created_at,
                        'updated_at' => $pago->updated_at,
                    ]);
                }

                if ($pagado > 0) {
                    DB::table('abonos')->updateOrInsert(
                        ['legacy_pago_id' => $pago->id_pag],
                        [
                            'fkventa' => $ventaId,
                            'fkcuota' => null,
                            'fkmetodo' => $pago->fkmetodo,
                            'fksede' => $pago->fksede,
                            'fkuser' => $pago->fkuser,
                            'monto' => $pagado,
                            'fecha_abono' => $pago->created_at,
                            'num_comprobante' => $pago->num_comprobante,
                            'observacion' => 'Abono migrado desde el pago #'.$pago->id_pag,
                            'created_at' => $pago->created_at,
                            'updated_at' => $pago->updated_at,
                        ]
                    );
                }

                DB::table('cuotas')->where('fkpago', $pago->id_pag)->whereNull('fkventa')
                    ->update(['fkventa' => $ventaId]);
            });

            DB::table('ventas')
                ->where('monto_pagado', '>', 0)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('abonos')->whereColumn('abonos.fkventa', 'ventas.id_venta'))
                ->orderBy('id_venta')
                ->get()
                ->each(function ($venta) {
                    DB::table('abonos')->insert([
                        'fkventa' => $venta->id_venta,
                        'fkcuota' => null,
                        'fkmetodo' => $venta->fkmetodo,
                        'fksede' => $venta->fksede,
                        'fkuser' => $venta->fkusers,
                        'monto' => min((float) $venta->monto_pagado, (float) $venta->venta_total),
                        'fecha_abono' => $venta->created_at,
                        'num_comprobante' => null,
                        'observacion' => 'Cobro inicial migrado desde la venta #'.$venta->id_venta,
                        'legacy_pago_id' => null,
                        'created_at' => $venta->created_at,
                        'updated_at' => $venta->updated_at,
                    ]);
                });
        });
    }

    public function down(): void
    {
        $ventasMigradas = Schema::hasColumn('ventas', 'legacy_pago_id')
            ? DB::table('ventas')->whereNotNull('legacy_pago_id')->pluck('id_venta')
            : collect();

        if ($ventasMigradas->isNotEmpty()) {
            DB::table('cuotas')->whereIn('fkventa', $ventasMigradas)->update(['fkventa' => null]);
            DB::table('membresias_alumno')->whereIn('fkventa', $ventasMigradas)->update(['fkventa' => null]);
            DB::table('ventas')->whereIn('id_venta', $ventasMigradas)->delete();
        }

        Schema::dropIfExists('abonos');

        if (Schema::hasColumn('membresias_alumno', 'fkventa')) {
            Schema::table('membresias_alumno', function (Blueprint $table) {
                $table->dropForeign(['fkventa']);
                $table->dropUnique(['fkventa']);
                $table->dropColumn('fkventa');
            });
        }

        if (Schema::hasColumn('ventas', 'legacy_pago_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropUnique(['legacy_pago_id']);
                $table->dropColumn('legacy_pago_id');
            });
        }
    }
};
