<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    protected $primaryKey = 'id_movimiento';

    protected $guarded = [];

    public $timestamps = true;

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'fkcaja', 'id_caja');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }
}
