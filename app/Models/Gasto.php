<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $table = 'gastos';

    protected $primaryKey = 'id_gasto';

    protected $guarded = [];

    public $timestamps = true;

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'fkcategoria', 'id_categoria');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por', 'id');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'rechazado');
    }

    public function getEstadoFormatoAttribute(): string
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
        ];

        return $estados[$this->estado] ?? 'Pendiente';
    }

    public function getFechaAprobacionFormatoAttribute(): string
    {
        return $this->fecha_aprobacion ? \Carbon\Carbon::parse($this->fecha_aprobacion)->format('d/m/Y H:i') : '-';
    }
}
