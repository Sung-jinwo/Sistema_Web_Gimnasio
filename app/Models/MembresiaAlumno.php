<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MembresiaAlumno extends Model
{
    protected $table = 'membresias_alumno';

    protected $primaryKey = 'id_membresia_alumno';

    protected $guarded = [];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'fkalumno', 'id_alumno');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'fkmem', 'id_mem');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa')
                    ->where('fecha_fin', '>=', now()->format('Y-m-d'));
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'activa')
                    ->where('fecha_fin', '<', now()->format('Y-m-d'));
    }

    public function scopePorVencer($query, $dias = 5)
    {
        $fechaLimite = now()->addDays($dias)->format('Y-m-d');
        return $query->where('estado', 'activa')
                    ->whereBetween('fecha_fin', [now()->format('Y-m-d'), $fechaLimite]);
    }

    public function getVigenteAttribute(): bool
    {
        return $this->estado === 'activa' && $this->fecha_fin >= now()->format('Y-m-d');
    }

    public function getDiasRestantesAttribute(): int
    {
        if (!$this->vigente) {
            return 0;
        }

        return now()->diffInDays(Carbon::parse($this->fecha_fin), false);
    }

    public function getEstadoFormatoAttribute(): string
    {
        if ($this->estado === 'cancelada') {
            return 'Cancelada';
        }

        if (!$this->vigente) {
            return 'Vencida';
        }

        if ($this->dias_restantes <= 5) {
            return 'Por vencer';
        }

        return 'Activa';
    }

    public function getFechaInicioFormatoAttribute(): string
    {
        return Carbon::parse($this->fecha_inicio)->format('d/m/Y');
    }

    public function getFechaFinFormatoAttribute(): string
    {
        return Carbon::parse($this->fecha_fin)->format('d/m/Y');
    }
}
