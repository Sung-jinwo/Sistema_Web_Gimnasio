<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    const STOCK_CRITICO = 0;

    protected $table = 'productos';
    protected $primaryKey = 'id_productos';
    protected $guarded = [];

    public $timestamps = true;


    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'fkcategoria', 'id_categoria');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'fkusers', 'id');
    }

    public function isStockCritico()
    {
        return $this->prod_cantidad <= self::STOCK_CRITICO;
    }
    // Sede
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }
}
