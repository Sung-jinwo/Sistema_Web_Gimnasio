<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $primaryKey = 'id_audit_log';

    protected $guarded = [];

    protected $casts = [
        'valores_antiguos' => 'array',
        'valores_nuevos' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fkuser', 'id');
    }

    public function scopePorUsuario($query, int $userId)
    {
        return $query->where('fkuser', $userId);
    }

    public function scopePorModulo($query, string $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePorAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopeRecientes($query, int $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }

    public function getFechaFormatoAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    public function getAccionFormatoAttribute(): string
    {
        $acciones = [
            'crear' => 'Creación',
            'editar' => 'Edición',
            'eliminar' => 'Eliminación',
            'aprobar' => 'Aprobación',
            'rechazar' => 'Rechazo',
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
        ];

        return $acciones[$this->accion] ?? ucfirst($this->accion);
    }
}
