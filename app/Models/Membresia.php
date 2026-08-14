<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $table = 'membresias';

    protected $primaryKey = 'id_mem';

    protected $guarded = [];

    public $timestamps = true;

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'fkmem', 'id_mem');
    }

    public function membresiasAlumno()
    {
        return $this->hasMany(MembresiaAlumno::class, 'fkmem', 'id_mem');
    }

    public function getActivaAttribute(): bool
    {
        return $this->estado === 'A';
    }

    public function getFechaLimiteAttribute()
    {
        return $this->mem_limit ? \Carbon\Carbon::parse($this->mem_limit)->format('d/m/y') : null;
    }
}
