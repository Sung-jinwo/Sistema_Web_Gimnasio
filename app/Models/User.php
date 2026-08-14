<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'fksede',
        'telefono',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    const ROL_ADMIN = 0;
    const ROL_EMPLEADO = 1;
    const ROL_ASISTENCIA = 2;
    const ROL_VENTAS = 3;

    const ROL_SPATIE_ADMIN = 'Administrador';
    const ROL_SPATIE_LOCAL = 'Local';
    const ROL_SPATIE_REDES = 'Redes';
    const ROL_SPATIE_ASISTENCIA = 'Asistencia';

    public function is($rol)
    {
        return $this->rol == $rol;
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public static function withRolesAdminAndEmpleado()
    {
        return self::whereIn('rol', [self::ROL_ADMIN, self::ROL_EMPLEADO, self::ROL_VENTAS])->get();
    }

    public function getNombreRolAttribute(): ?string
    {
        if ($this->roles->isNotEmpty()) {
            return $this->roles->first()->name;
        }

        $arr = [
            self::ROL_ADMIN => 'Administrador',
            self::ROL_EMPLEADO => 'Empleado',
            self::ROL_ASISTENCIA => 'Asistencia',
            self::ROL_VENTAS => 'Asesor de ventas',
        ];

        return $arr[$this->rol] ?? null;
    }
}
