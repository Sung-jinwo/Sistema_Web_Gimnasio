<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $primaryKey = 'id_categoria';

    protected $guarded = [];

    public $timestamps = true;

    public function productos()
    {
        return $this->hasMany(Producto::class, 'fkcategoria', 'id_categoria');
    }
}
