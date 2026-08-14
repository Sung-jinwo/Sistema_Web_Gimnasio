<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function registrar(
        string $accion,
        string $modulo,
        string $modelo,
        int $modeloId,
        ?array $valoresAntiguos = null,
        ?array $valoresNuevos = null,
        ?int $userId = null
    ): AuditLog {
        return AuditLog::create([
            'fkuser' => $userId ?? auth()->id(),
            'accion' => $accion,
            'modulo' => $modulo,
            'modelo' => $modelo,
            'modelo_id' => $modeloId,
            'valores_antiguos' => $valoresAntiguos,
            'valores_nuevos' => $valoresNuevos,
            'ip_address' => Request::ip(),
        ]);
    }

    public function registrarCreacion(string $modulo, string $modelo, int $modeloId, array $valoresNuevos, ?int $userId = null): AuditLog
    {
        return $this->registrar('crear', $modulo, $modelo, $modeloId, null, $valoresNuevos, $userId);
    }

    public function registrarEdicion(string $modulo, string $modelo, int $modeloId, array $valoresAntiguos, array $valoresNuevos, ?int $userId = null): AuditLog
    {
        return $this->registrar('editar', $modulo, $modelo, $modeloId, $valoresAntiguos, $valoresNuevos, $userId);
    }

    public function registrarEliminacion(string $modulo, string $modelo, int $modeloId, array $valoresAntiguos, ?int $userId = null): AuditLog
    {
        return $this->registrar('eliminar', $modulo, $modelo, $modeloId, $valoresAntiguos, null, $userId);
    }

    public function registrarAprobacion(string $modulo, string $modelo, int $modeloId, array $valoresNuevos, ?int $userId = null): AuditLog
    {
        return $this->registrar('aprobar', $modulo, $modelo, $modeloId, null, $valoresNuevos, $userId);
    }

    public function registrarRechazo(string $modulo, string $modelo, int $modeloId, array $valoresNuevos, ?int $userId = null): AuditLog
    {
        return $this->registrar('rechazar', $modulo, $modelo, $modeloId, null, $valoresNuevos, $userId);
    }

    public function obtenerLogs(array $filtros = [], int $perPage = 20)
    {
        $query = AuditLog::with('usuario');

        if (!empty($filtros['usuario'])) {
            $query->porUsuario($filtros['usuario']);
        }

        if (!empty($filtros['modulo'])) {
            $query->porModulo($filtros['modulo']);
        }

        if (!empty($filtros['accion'])) {
            $query->porAccion($filtros['accion']);
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_fin']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
