<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $table = 'comisiones';

    protected $primaryKey = 'id_comision';

    protected $guarded = [];

    public $timestamps = true;

    protected static function booted(): void
    {
        static::creating(function (Comision $comision) {
            $comision->monto ??= $comision->comision_base ?? 0;
        });
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'fkventa', 'id_venta');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'fkcaja', 'id_caja');
    }

    public function getEstadoFormatoAttribute(): string
    {
        return $this->estado === 'liquidada' ? 'Liquidada' : 'Pendiente';
    }

    public function getFechaAcordadaFormatoAttribute(): string
    {
        return $this->fecha_acordada_pago ? \Carbon\Carbon::parse($this->fecha_acordada_pago)->format('d/m/Y') : '-';
    }

    public function getFechaPagoRealFormatoAttribute(): string
    {
        return $this->fecha_pago_real ? \Carbon\Carbon::parse($this->fecha_pago_real)->format('d/m/Y') : '-';
    }

    public function getDiasRetrasoAttribute(): int
    {
        if (! $this->fecha_acordada_pago || ! $this->fecha_pago_real) {
            return 0;
        }

        return max(0, \Carbon\Carbon::parse($this->fecha_acordada_pago)->diffInDays($this->fecha_pago_real));
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeLiquidadas($query)
    {
        return $query->where('estado', 'liquidada');
    }

    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('fkuser', $usuarioId);
    }
}
