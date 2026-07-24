<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';

    protected $primaryKey = 'id_detalle';

    protected $guarded = [];

    public $timestamps = true;

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'fkventa', 'id_venta');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'fkproducto', 'id_productos');
    }
}
