<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoDetalle extends Model
{
    protected $table = 'pago_detalles';

    protected $primaryKey = 'id_detalle';

    protected $guarded = [];

    public $timestamps = true;

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'fkpago', 'id_pag');
    }
}
