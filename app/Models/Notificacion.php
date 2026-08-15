<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $primaryKey = 'id_notificacion';

    protected $guarded = [];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_expiracion' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeLeidas($query)
    {
        return $query->where('leida', true);
    }

    public function scopeNoExpiradas($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_expiracion')
                ->orWhere('fecha_expiracion', '>', now());
        });
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function getFechaFormatoAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function getIconoAttribute(): string
    {
        $iconos = [
            'membresia_por_vencer' => 'fa-clock',
            'membresia_vencida' => 'fa-exclamation-triangle',
            'pago_pendiente' => 'fa-money-bill',
            'pago_vencido' => 'fa-exclamation-circle',
            'cierre_pendiente' => 'fa-cash-register',
        ];

        return $iconos[$this->tipo] ?? 'fa-bell';
    }

    public function getColorAttribute(): string
    {
        $colores = [
            'membresia_por_vencer' => 'yellow',
            'membresia_vencida' => 'red',
            'pago_pendiente' => 'blue',
            'pago_vencido' => 'red',
            'cierre_pendiente' => 'orange',
        ];

        return $colores[$this->tipo] ?? 'gray';
    }
}
