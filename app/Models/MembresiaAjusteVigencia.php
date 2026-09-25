<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembresiaAjusteVigencia extends Model
{
    protected $table = 'membresia_ajustes_vigencia';

    protected $primaryKey = 'id_ajuste';

    protected $guarded = [];

    protected $casts = [
        'fecha_anterior' => 'date',
        'fecha_nueva' => 'date',
    ];

    public function membresiaAlumno()
    {
        return $this->belongsTo(MembresiaAlumno::class, 'fkmembresia_alumno', 'id_membresia_alumno');
    }

    public function administrador()
    {
        return $this->belongsTo(User::class, 'fkadmin', 'id');
    }
}
