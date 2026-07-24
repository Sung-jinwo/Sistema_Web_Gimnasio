<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comision extends Model
{
    protected $table = 'comisiones';

    protected $primaryKey = 'id_comision';

    protected $guarded = [];

    public $timestamps = true;

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
}
