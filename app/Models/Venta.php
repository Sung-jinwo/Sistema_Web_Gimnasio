<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $primaryKey = 'id_venta';

    protected $guarded = [];

    public $timestamps = true;

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'fkalum', 'id_alumno');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkusers', 'id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function metodo()
    {
        return $this->belongsTo(MetodoPago::class, 'fkmetodo', 'id_metod');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'fkventa', 'id_venta');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'fkproducto', 'id_productos');
    }

    public function getFechaReservaAttribute($value)
    {
        return Carbon::parse($this->venta_fecha)->format('d/m/Y');
    }

    public function getReservaPorVencerAttribute()
    {
        $fecha = Carbon::parse($this->venta_fecha);
        $diasRestantes = now()->diffInDays($fecha, false);

        return $diasRestantes >= 0 && $diasRestantes <= 5;
    }

    // Esta función dice si ya está vencida (hoy o antes)
    public function getReservaVencidaAttribute()
    {
        $fecha = Carbon::parse($this->venta_fecha);

        return $fecha->lt(now()->startOfDay());
    }

    public function getMensajeReservaPorVencerAttribute()
    {
        if ($this->reserva_por_vencer) {
            $dias = now()->diffInDays($this->venta_fecha);

            return "La Venta Reservada de producto para {$this->alumno?->alum_nombre} vence en {$dias} día(s).";
        }

        return null;
    }

    // Mensaje para reservas vencidas
    public function getMensajeReservaVencidaAttribute()
    {
        if ($this->reserva_vencida) {
            $dias = now()->diffInDays($this->venta_fecha);

            return "¡ATENCIÓN! Reserva para {$this->alumno?->alum_nombre} vencida hace {$dias} día(s).";
        }

        return null;
    }
}
