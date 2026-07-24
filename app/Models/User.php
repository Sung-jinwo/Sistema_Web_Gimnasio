<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use /**HasApiTokens, */ HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'fksede',
        'telefono',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
        $arr = [
            self::ROL_ADMIN => 'Administrador',
            self::ROL_EMPLEADO => 'Empleado',
            self::ROL_ASISTENCIA => 'Asistencia',
            self::ROL_VENTAS => 'Asesor de ventas',
        ];

        return $arr[$this->rol] ?? null;
    }
}
