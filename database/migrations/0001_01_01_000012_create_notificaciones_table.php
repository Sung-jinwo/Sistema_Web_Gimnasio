<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->bigIncrements('id_notificacion');
            $table->unsignedBigInteger('fkuser');
            $table->string('tipo', 50);
            $table->string('titulo', 200);
            $table->text('mensaje');
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->boolean('leida')->default(false);
            $table->datetime('fecha_expiracion')->nullable();
            $table->timestamps();

            $table->foreign('fkuser')->references('id')->on('users')->onDelete('cascade');
            $table->index(['fkuser', 'leida']);
            $table->index('tipo');
            $table->index('fecha_expiracion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
