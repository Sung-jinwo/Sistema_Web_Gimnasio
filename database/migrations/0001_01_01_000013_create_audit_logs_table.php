<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id_audit_log');
            $table->unsignedBigInteger('fkuser')->nullable();
            $table->string('accion', 50);
            $table->string('modulo', 50);
            $table->string('modelo', 100);
            $table->unsignedBigInteger('modelo_id');
            $table->json('valores_antiguos')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('fkuser')->references('id')->on('users')->nullOnDelete();
            $table->index(['fkuser', 'created_at']);
            $table->index(['modulo', 'accion']);
            $table->index(['modelo', 'modelo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
