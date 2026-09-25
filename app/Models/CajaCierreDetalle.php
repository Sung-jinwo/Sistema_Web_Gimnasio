<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaCierreDetalle extends Model
{
    protected $table = 'caja_cierre_detalles';

    protected $primaryKey = 'id_detalle_cierre';

    protected $guarded = [];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'fkcaja', 'id_caja');
    }

    public function metodo()
    {
        return $this->belongsTo(MetodoPago::class, 'fkmetodo', 'id_metod');
    }
}
