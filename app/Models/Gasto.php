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
}
