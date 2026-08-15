<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $table = 'cuotas';

    protected $primaryKey = 'id_cuota';

    protected $guarded = [];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'fkventa', 'id_venta');
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'fkpago', 'id_pag');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'vencida')
            ->orWhere(function ($q) {
                $q->where('estado', 'pendiente')
                    ->where('fecha_acordada', '<', now()->format('Y-m-d'));
            });
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagada');
    }

    public function getEstadoFormatoAttribute(): string
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'parcial' => 'Parcial',
            'pagada' => 'Pagada',
            'vencida' => 'Vencida',
        ];

        return $estados[$this->estado] ?? 'Pendiente';
    }

    public function getDiasVencidaAttribute(): int
    {
        if ($this->estado !== 'vencida' && $this->fecha_acordada < now()->format('Y-m-d')) {
            return now()->diffInDays(Carbon::parse($this->fecha_acordada), false);
        }

        return 0;
    }

    public function getFechaAcordadaFormatoAttribute(): string
    {
        return Carbon::parse($this->fecha_acordada)->format('d/m/Y');
    }

    public function getFechaPagoRealFormatoAttribute(): string
    {
        return $this->fecha_pago_real ? Carbon::parse($this->fecha_pago_real)->format('d/m/Y') : '-';
    }
}
