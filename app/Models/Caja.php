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
        return is_null($this->fecha_cierre);
    }

    public function getSaldoActualAttribute(): float
    {
        $ingresos = $this->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = $this->movimientos()->where('tipo', 'egreso')->sum('monto');

        return ($this->monto_inicial ?? 0) + $ingresos - $egresos;
    }
}
