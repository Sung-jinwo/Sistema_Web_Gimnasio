<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    protected $table = 'membresias';
    protected $primaryKey = 'id_mem';
    protected $guarded = [];

    public $timestamps = true;

     public function getEstadoMembresiaAttribute()
    {
        return $this
            ->where('estado', 'A')
            ->latest()
            ->first();
    }

    public function pago()
    {
        return $this->hasOne(Pagos::class, 'fkmem', 'id_mem');
    }
    
    public function getFechaLimiteAttribute()
    {
        return $this->mem_limit ? \Carbon\Carbon::parse($this->mem_limit)->format('d/m/y') : null;
    }

}
