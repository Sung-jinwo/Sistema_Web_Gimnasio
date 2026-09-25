<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $primaryKey = 'id_venta';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'anulada_at' => 'datetime',
        'venta_fecha' => 'date',
        'fecha_acordada' => 'date',
        'pagada_at' => 'datetime',
        'stock_liberado_at' => 'datetime',
    ];

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'fkalum', 'id_alumno');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkusers', 'id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'fkcaja', 'id_caja');
    }

    public function metodo()
    {
        return $this->belongsTo(MetodoPago::class, 'fkmetodo', 'id_metod');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'fkventa', 'id_venta');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'fkproducto', 'id_productos');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'fkmem', 'id_mem');
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class, 'fkventa', 'id_venta');
    }

    public function abonos()
    {
        return $this->hasMany(Abono::class, 'fkventa', 'id_venta');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'fkventa', 'id_venta');
    }

    public function membresiaAlumno()
    {
        return $this->hasOne(MembresiaAlumno::class, 'fkventa', 'id_venta');
    }
}
