<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abono extends Model
{
    protected $table = 'abonos';

    protected $primaryKey = 'id_abono';

    protected $guarded = [];

    protected $casts = [
        'fecha_abono' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'fkventa', 'id_venta');
    }

    public function cuota()
    {
        return $this->belongsTo(Cuota::class, 'fkcuota', 'id_cuota');
    }

    public function metodo()
    {
        return $this->belongsTo(MetodoPago::class, 'fkmetodo', 'id_metod');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'fkcaja', 'id_caja');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }
}
