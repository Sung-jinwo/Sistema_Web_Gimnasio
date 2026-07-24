<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaGasto extends Model
{
    protected $table = 'categorias_gasto';

    protected $primaryKey = 'id_categoria';

    protected $guarded = [];

    public $timestamps = false;

    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'fkcategoria', 'id_categoria');
    }
}
