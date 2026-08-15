<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->decimal('total_ingresos_esperado', 10, 2)->default(0)->after('monto_final');
            $table->decimal('total_egresos', 10, 2)->default(0)->after('total_ingresos_esperado');
            $table->decimal('monto_entregado', 10, 2)->nullable()->after('total_egresos');
            $table->decimal('diferencia', 10, 2)->nullable()->after('monto_entregado');

            $table->enum('estado', ['abierta', 'cerrada', 'anulada'])->default('abierta')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropColumn([
                'total_ingresos_esperado',
                'total_egresos',
                'monto_entregado',
                'diferencia',
            ]);

            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta')->change();
        });
    }
};
