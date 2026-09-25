<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumno';

    protected $primaryKey = 'id_alumno';

    protected $guarded = [];

    public $timestamps = true;

    // protected $fillable = [
    // 'alum_codigo',
    // 'alum_nombre',
    // 'alum_apellido',
    // 'fksexo',
    // 'fecha_nac',
    // 'fksede',
    // 'alum_documento',
    // 'alum_numDoc',
    // 'alum_telefo',
    // 'alum_correro',
    // 'alum_direccion',
    // 'alum_condi',
    // 'alum_estado',
    // 'fkuser'
    // ];

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'fkalum', 'id_alumno');
    }

    public function padres()
    {
        return $this->hasMany(Padre::class, 'fkalumno', 'id_alumno');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'fkalum', 'id_alumno');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'fkalum', 'id_alumno');
    }

    public function sexo()
    {
        return $this->belongsTo(Sexo::class, 'fksexo', 'id_sexo');
    }

    public function membresiasAlumno()
    {
        return $this->hasMany(MembresiaAlumno::class, 'fkalumno', 'id_alumno');
    }

    public function membresiaActiva()
    {
        return $this->hasOne(MembresiaAlumno::class, 'fkalumno', 'id_alumno')
            ->where('estado', 'activa')
            ->where('fecha_fin', '>=', now()->format('Y-m-d'))
            ->latest('fecha_inicio');
    }

    public function getMembresiaVigenteAttribute()
    {
        return $this->membresiasAlumno()
            ->with(['membresia', 'venta'])
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    /**
     * Indica si corresponde ofrecer una membresía: sin membresía activa
     * o con vencimiento dentro de los próximos 5 días.
     */
    public function getRequiereMembresiaAttribute(): bool
    {
        $activa = $this->membresiaActiva;

        if (! $activa) {
            return true;
        }

        return Carbon::parse($activa->fecha_fin)->lte(now()->addDays(5)->endOfDay());
    }

    public function scopeConEstadoMembresia($query, $estado)
    {
        $hoy = now()->format('Y-m-d');

        switch ($estado) {
            case 'vigente':
                return $query->whereHas('membresiasAlumno', function ($q) use ($hoy) {
                    $q->where('estado', 'activa')->where('fecha_fin', '>=', $hoy);
                });

            case 'por_caducar':
                $fechaLimite = now()->addDays(5)->format('Y-m-d');

                return $query->whereHas('membresiasAlumno', function ($q) use ($hoy, $fechaLimite) {
                    $q->where('estado', 'activa')->whereBetween('fecha_fin', [$hoy, $fechaLimite]);
                });

            case 'vencido':
                return $query->whereHas('membresiasAlumno', function ($q) use ($hoy) {
                    $q->where('fecha_fin', '<', $hoy);
                });

            case 'sin_membresia':
                return $query->whereDoesntHave('membresiasAlumno');
        }

        return $query;
    }

    public function getAlumEdaAttribute(): int // alum_edad = alumEda
    {
        $now = now();

        $fechaNac = Carbon::parse($this->fecha_nac);

        return $fechaNac->diffInYears($now);
    }

    public function getAlumnoFormatoAttribute(): string
    {
        return Carbon::parse($this->fecha_nac)->format('d/m/Y');
    }

    public function getRegistroFormatoAttribute(): string
    {
        return 'dia'.Carbon::parse($this->created_at)->format('d').' de '.Carbon::parse($this->created_at)->locale('es')->monthName.' del '.Carbon::parse($this->created_at)->format('Y');
    }

    public function getAlumnoCreatedAttribute(): string
    {
        return Carbon::parse($this->created_at)->format('d/m/Y');
    }

    public function getEstadoMembresiaAttribute(): array
    {
        $pago = $this->membresiaVigente;

        if (! $pago || ! $pago->fecha_fin) {
            return [
                'estado' => 'Sin membresía',
                'clase' => 'status-inactive',
                'fecha_fin' => null,
            ];
        }

        $fechaFin = Carbon::parse($pago->fecha_fin);
        $diferencia = now()->diffInDays($fechaFin, false);

        if ($diferencia < 0) {
            return [
                'estado' => 'Vencido',
                'clase' => 'status-expired',
                'fecha_fin' => $fechaFin,
            ];
        }

        if ($diferencia <= 5) {
            return [
                'estado' => 'Por caducar / Renovar',
                'clase' => 'status-expiring',
                'fecha_fin' => $fechaFin,
            ];
        }

        return [
            'estado' => 'Vigente',
            'clase' => 'status-active',
            'fecha_fin' => $fechaFin,
        ];
    }

    public function getClaseEstadoAttribute(): string
    {
        return $this->estado_membresia['clase'];
    }

    public function getTextoMembresiaAttribute(): string
    {
        return $this->estado_membresia['estado'];
    }

    public function getEstadoPagoAttribute()
    {
        $pagoPrincipal = $this->membresiaVigente;

        return $pagoPrincipal?->venta?->estado_pago ?? 'Sin pago';

    }

    public function getClasePagoAttribute(): string
    {
        $arr = [
            'pagado' => 'status-active',
            'parcial' => 'status-expiring',
        ];

        return $arr[$this->estadoPago] ?? 'status-inactive';
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->alum_nombre.' '.$this->alum_apellido;
    }

    public function setAlumNombreAttribute($value)
    {
        $this->attributes['alum_nombre'] = mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }

    public function setAlumApellidoAttribute($value)
    {
        $this->attributes['alum_apellido'] = mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');
    }
}
