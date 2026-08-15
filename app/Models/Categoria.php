<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $primaryKey = 'id_categoria';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = ['cat_estado' => 'boolean'];

    public function productos()
    {
        return $this->hasMany(Producto::class, 'fkcategoria', 'id_categoria');
    }
}
