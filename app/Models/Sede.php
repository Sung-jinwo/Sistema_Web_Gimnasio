<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    protected $table = 'sedes';

    protected $primaryKey = 'id_sede';

    protected $guarded = [];

    public $timestamps = true;

    public function alumnos()
    {
        return $this->hasMany(Alumno::class, 'fksede', 'id_sede');
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'fksede', 'id_sede');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'fksede', 'id_sede');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'fksede', 'id_sede');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'fksede', 'id_sede');
    }

    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'fksede', 'id_sede');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'fksede', 'id_sede');
    }

    public function cajas()
    {
        return $this->hasMany(Caja::class, 'fksede', 'id_sede');
    }
}
