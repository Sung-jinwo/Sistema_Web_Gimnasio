<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id('id_sede');
            $table->string('sede_nombre', 100);
            $table->string('sede_direccion', 200)->nullable();
            $table->string('sede_telefono', 20)->nullable();
            $table->string('sede_responsable', 100)->nullable();
            $table->string('sede_horario', 100)->nullable();
            $table->boolean('sede_estado')->default(true);
            $table->timestamps();
        });

        Schema::create('sexo', function (Blueprint $table) {
            $table->id('id_sexo');
            $table->string('sexo_nombre', 20);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('telefono', 20)->nullable();
            $table->unsignedTinyInteger('rol')->default(1);
            $table->unsignedBigInteger('fksede')->nullable();
            $table->boolean('estado')->default(true);
            $table->foreign('fksede')->references('id_sede')->on('sedes')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sexo');
        Schema::dropIfExists('sedes');
    }
};
