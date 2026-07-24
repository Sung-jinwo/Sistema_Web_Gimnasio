<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'visitas';
    protected $primaryKey = 'id_visi';
    protected $guarded = [];
    protected $casts = [
        'visi_fecha' => 'datetime'
    ];

    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($asistencia) {
            if (!$asistencia->visi_fecha) {
                $asistencia->visi_fecha = now();
            }
        });
    }

        public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'fkalum', 'id_alumno');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function getVisiFechaFormatoAttribute()
    {
        return $this->visi_fecha->format('d/m/Y H:i:s');
    }
}
