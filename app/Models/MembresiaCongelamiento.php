<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembresiaCongelamiento extends Model
{
    protected $table = 'membresia_congelamientos';

    protected $primaryKey = 'id_congelamiento';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public const ESTADO_PROGRAMADO = 'programado';

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_FINALIZADO = 'finalizado';

    public const ESTADO_CANCELADO = 'cancelado';

    public function membresiaAlumno()
    {
        return $this->belongsTo(MembresiaAlumno::class, 'fkmembresia_alumno', 'id_membresia_alumno');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function estaVigente(string $fecha): bool
    {
        return in_array($this->estado, [self::ESTADO_PROGRAMADO, self::ESTADO_ACTIVO], true)
            && $this->fecha_inicio->format('Y-m-d') <= $fecha
            && $fecha <= $this->fecha_fin->format('Y-m-d');
    }
}
