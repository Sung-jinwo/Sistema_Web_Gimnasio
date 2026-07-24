<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id('id_metod');
            $table->string('metod_nombre', 50);
            $table->timestamps();
        });

        Schema::create('membresias', function (Blueprint $table) {
            $table->id('id_mem');
            $table->string('mem_nombre', 100);
            $table->decimal('mem_precio', 10, 2);
            $table->unsignedTinyInteger('mem_duracion')->comment('Duracion en dias');
            $table->enum('mem_categoria', ['Regular', 'Premium', 'VIP'])->default('Regular');
            $table->enum('mem_tipo', ['Diaria', 'Semanal', 'Mensual', 'Trimestral', 'Semestral', 'Anual']);
            $table->text('mem_beneficios')->nullable();
            $table->char('estado', 1)->default('A');
            $table->date('mem_limit')->nullable();
            $table->timestamps();
        });

        Schema::create('alumno', function (Blueprint $table) {
            $table->id('id_alumno');
            $table->string('alum_codigo', 20)->nullable()->unique();
            $table->string('alum_nombre', 100);
            $table->string('alum_apellido', 100);
            $table->unsignedBigInteger('fksexo');
            $table->date('fecha_nac');
            $table->unsignedBigInteger('fksede');
            $table->string('alum_documento', 30)->nullable();
            $table->string('alum_numDoc', 20)->nullable();
            $table->string('alum_telefo', 20)->nullable();
            $table->string('alum_correro', 100)->nullable();
            $table->string('alum_direccion', 200)->nullable();
            $table->text('alum_condi')->nullable();
            $table->boolean('alum_estado')->default(true);
            $table->unsignedBigInteger('fkuser');
            $table->foreign('fksexo')->references('id_sexo')->on('sexo');
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->foreign('fkuser')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('alum_nombre');
            $table->index('alum_numDoc');
        });

        Schema::create('padres', function (Blueprint $table) {
            $table->id('id_padre');
            $table->string('padre_nombre', 100);
            $table->string('padre_apellido', 100)->nullable();
            $table->string('padre_telefono', 20)->nullable();
            $table->string('padre_parentesco', 30)->nullable();
            $table->unsignedBigInteger('fkalumno');
            $table->foreign('fkalumno')->references('id_alumno')->on('alumno')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pag');
            $table->unsignedBigInteger('fkalum');
            $table->unsignedBigInteger('fkuser');
            $table->unsignedBigInteger('fksede');
            $table->unsignedBigInteger('fkmetodo');
            $table->unsignedBigInteger('fkmem');
            $table->enum('tipo_membresia', ['principal', 'secundaria'])->default('principal');
            $table->date('pag_inicio');
            $table->date('pag_fin');
            $table->date('fecha_limite_pago')->nullable();
            $table->enum('estado_pago', ['completo', 'incompleto', 'reservado'])->default('completo');
            $table->decimal('pag_monto', 10, 2)->nullable();
            $table->decimal('pag_descuento', 10, 2)->default(0);
            $table->string('num_comprobante', 50)->nullable();
            $table->text('observacion')->nullable();
            $table->foreign('fkalum')->references('id_alumno')->on('alumno');
            $table->foreign('fkuser')->references('id')->on('users');
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->foreign('fkmetodo')->references('id_metod')->on('metodos_pago');
            $table->foreign('fkmem')->references('id_mem')->on('membresias');
            $table->timestamps();

            $table->index('fkalum');
            $table->index('estado_pago');
        });

        Schema::create('visitas', function (Blueprint $table) {
            $table->id('id_visi');
            $table->unsignedBigInteger('fkalum');
            $table->unsignedBigInteger('fkuser');
            $table->unsignedBigInteger('fksede');
            $table->dateTime('visi_fecha');
            $table->enum('tipo_ingreso', ['codigo', 'dni', 'qr', 'huella'])->default('codigo');
            $table->foreign('fkalum')->references('id_alumno')->on('alumno');
            $table->foreign('fkuser')->references('id')->on('users');
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->timestamps();

            $table->index('visi_fecha');
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('cat_nombre', 50);
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id('id_productos');
            $table->string('prod_codigo', 20)->nullable()->unique();
            $table->string('prod_nombre', 100);
            $table->decimal('prod_precio', 10, 2);
            $table->string('prod_marca', 50)->nullable();
            $table->integer('prod_cantidad')->default(0);
            $table->integer('prod_stock_minimo')->default(5);
            $table->string('prod_imagen', 255)->nullable();
            $table->unsignedBigInteger('fkcategoria');
            $table->unsignedBigInteger('fkusers');
            $table->unsignedBigInteger('fksede');
            $table->foreign('fkcategoria')->references('id_categoria')->on('categorias');
            $table->foreign('fkusers')->references('id')->on('users');
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->timestamps();

            $table->index('prod_nombre');
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id('id_venta');
            $table->unsignedBigInteger('fkalum');
            $table->unsignedBigInteger('fkusers');
            $table->unsignedBigInteger('fksede');
            $table->unsignedBigInteger('fkmetodo');
            $table->unsignedBigInteger('fkproducto')->nullable();
            $table->date('venta_fecha')->nullable();
            $table->enum('estado_venta', ['completado', 'reservado', 'incompleto'])->default('completado');
            $table->decimal('venta_total', 10, 2)->default(0);
            $table->decimal('venta_descuento', 10, 2)->default(0);
            $table->text('observacion')->nullable();
            $table->foreign('fkalum')->references('id_alumno')->on('alumno');
            $table->foreign('fkusers')->references('id')->on('users');
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->foreign('fkmetodo')->references('id_metod')->on('metodos_pago');
            $table->foreign('fkproducto')->references('id_productos')->on('productos')->nullOnDelete();
            $table->timestamps();

            $table->index('estado_venta');
        });

        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('fkventa');
            $table->unsignedBigInteger('fkproducto');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->foreign('fkventa')->references('id_venta')->on('ventas')->cascadeOnDelete();
            $table->foreign('fkproducto')->references('id_productos')->on('productos');
            $table->timestamps();
        });

        Schema::create('pago_detalles', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('fkpago');
            $table->string('concepto', 200);
            $table->decimal('monto', 10, 2);
            $table->foreign('fkpago')->references('id_pag')->on('pagos')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('categorias_gasto', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('cat_nombre', 50);
        });

        Schema::create('gastos', function (Blueprint $table) {
            $table->id('id_gasto');
            $table->unsignedBigInteger('fksede');
            $table->unsignedBigInteger('fkuser');
            $table->unsignedBigInteger('fkcategoria')->nullable();
            $table->date('gas_fecha');
            $table->string('gas_concepto', 200);
            $table->decimal('gas_monto', 10, 2);
            $table->text('gas_observacion')->nullable();
            $table->string('gas_comprobante', 255)->nullable();
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->foreign('fkuser')->references('id')->on('users');
            $table->foreign('fkcategoria')->references('id_categoria')->on('categorias_gasto')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cajas', function (Blueprint $table) {
            $table->id('id_caja');
            $table->unsignedBigInteger('fksede');
            $table->unsignedBigInteger('fkuser');
            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('monto_inicial', 10, 2)->default(0);
            $table->decimal('monto_final', 10, 2)->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observacion')->nullable();
            $table->foreign('fksede')->references('id_sede')->on('sedes');
            $table->foreign('fkuser')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->unsignedBigInteger('fkcaja');
            $table->unsignedBigInteger('fkuser');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->decimal('monto', 10, 2);
            $table->string('concepto', 200);
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->foreign('fkcaja')->references('id_caja')->on('cajas')->cascadeOnDelete();
            $table->foreign('fkuser')->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('comisiones', function (Blueprint $table) {
            $table->id('id_comision');
            $table->unsignedBigInteger('fkuser');
            $table->unsignedBigInteger('fkcaja')->nullable();
            $table->unsignedBigInteger('fkventa')->nullable();
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->decimal('monto', 10, 2);
            $table->enum('tipo', ['venta', 'membresia'])->default('venta');
            $table->enum('estado', ['pendiente', 'liquidada'])->default('pendiente');
            $table->foreign('fkuser')->references('id')->on('users');
            $table->foreign('fkcaja')->references('id_caja')->on('cajas')->nullOnDelete();
            $table->foreign('fkventa')->references('id_venta')->on('ventas')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('cajas');
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('categorias_gasto');
        Schema::dropIfExists('pago_detalles');
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('visitas');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('padres');
        Schema::dropIfExists('alumno');
        Schema::dropIfExists('membresias');
        Schema::dropIfExists('metodos_pago');
    }
};
