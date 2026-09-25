<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'comision')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->decimal('comision', 10, 2)->default(0)->after('prod_precio');
            });
        }

        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'fkmem')) {
                $table->unsignedBigInteger('fkmem')->nullable()->after('fkproducto');
                $table->foreign('fkmem')->references('id_mem')->on('membresias')->nullOnDelete();
            }
            if (! Schema::hasColumn('ventas', 'pagada_at')) {
                $table->dateTime('pagada_at')->nullable()->after('fecha_acordada');
            }
            if (! Schema::hasColumn('ventas', 'stock_liberado_at')) {
                $table->dateTime('stock_liberado_at')->nullable()->after('pagada_at');
            }
        });

        DB::table('membresias_alumno')
            ->whereNotNull('fkventa')
            ->orderBy('id_membresia_alumno')
            ->each(function ($asignacion) {
                DB::table('ventas')
                    ->where('id_venta', $asignacion->fkventa)
                    ->whereNull('fkmem')
                    ->update(['fkmem' => $asignacion->fkmem]);
            });

        DB::table('ventas')->where('saldo', '<=', 0)->whereNull('pagada_at')->orderBy('id_venta')->each(function ($venta) {
            DB::table('ventas')->where('id_venta', $venta->id_venta)->update([
                'pagada_at' => $venta->updated_at ?? $venta->created_at ?? now(),
            ]);
        });

        DB::table('ventas')->where('estado_venta', 'reservado')->orderBy('id_venta')->each(function ($venta) {
            $cambios = [];
            if ((float) $venta->saldo <= 0 || $venta->tipo_venta === 'membresia') {
                $cambios['estado_venta'] = 'completado';
            }
            if ($venta->tipo_venta === 'producto' && (float) $venta->saldo > 0 && empty($venta->fecha_acordada) && ! empty($venta->venta_fecha)) {
                $cambios['fecha_acordada'] = $venta->venta_fecha;
            }
            if ($cambios !== []) {
                DB::table('ventas')->where('id_venta', $venta->id_venta)->update($cambios);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'fkmem')) {
                $table->dropForeign(['fkmem']);
                $table->dropColumn('fkmem');
            }
            $columnas = array_values(array_filter(['pagada_at', 'stock_liberado_at'], fn ($columna) => Schema::hasColumn('ventas', $columna)));
            if ($columnas !== []) {
                $table->dropColumn($columnas);
            }
        });

        if (Schema::hasColumn('productos', 'comision')) {
            Schema::table('productos', fn (Blueprint $table) => $table->dropColumn('comision'));
        }
    }
};
