<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->decimal('total', 10, 2)->default(0)->after('pag_monto');
            $table->decimal('monto_pagado', 10, 2)->default(0)->after('total');
            $table->decimal('saldo', 10, 2)->default(0)->after('monto_pagado');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['total', 'monto_pagado', 'saldo']);
        });
    }
};
