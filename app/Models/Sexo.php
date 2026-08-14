<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sexo extends Model
{
    protected $table = 'sexo';

    protected $primaryKey = 'id_sexo';

    public $timestamps = false;

    protected $guarded = [];

    public function alumnos()
    {
        return $this->hasMany(Alumno::class, 'fksexo', 'id_sexo');
    }
}
