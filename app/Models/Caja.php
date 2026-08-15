<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $primaryKey = 'id_caja';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'fkcaja', 'id_caja');
    }

    public function getAbiertaAttribute(): bool
    {
        return $this->estado === 'abierta';
    }

    public function getSaldoActualAttribute(): float
    {
        $ingresos = $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = $this->movimientos()->where('tipo', 'egreso')->sum('monto');

        return ($this->monto_inicial ?? 0) + $ingresos - $egresos;
    }

    public function getEstadoFormatoAttribute(): string
    {
        $estados = [
            'abierta' => 'Abierta',
            'cerrada' => 'Cerrada',
            'anulada' => 'Anulada',
        ];

        return $estados[$this->estado] ?? 'Abierta';
    }

    public function getFechaAperturaFormatoAttribute(): string
    {
        return $this->fecha_apertura ? $this->fecha_apertura->format('d/m/Y H:i') : '-';
    }

    public function getFechaCierreFormatoAttribute(): string
    {
        return $this->fecha_cierre ? $this->fecha_cierre->format('d/m/Y H:i') : '-';
    }

    public function getDiferenciaFormatoAttribute(): string
    {
        if ($this->diferencia === null) {
            return '-';
        }

        $color = $this->diferencia > 0 ? 'text-green-600' : ($this->diferencia < 0 ? 'text-red-600' : 'text-gray-600');

        return $color;
    }

    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estado', 'cerrada');
    }

    public function scopeAnuladas($query)
    {
        return $query->where('estado', 'anulada');
    }
}
