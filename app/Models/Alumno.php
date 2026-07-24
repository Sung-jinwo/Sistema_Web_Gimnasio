<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
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
        return $this->hasMany(Pagos::class, 'fkalum', 'id_alumno');
    }

    public function padres()
    {
        return $this->hasMany(Padres::class, 'fkalumno', 'id_alumno');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'fksede', 'id_sede');
    }

    public function mensaje()
    {
        return $this->hasOne(Mensaje::class, 'fkalum', 'id_alumno');
    }

    public function asistencia()
    {
        return $this->hasMany(Asistensias::class, 'fkalum', 'id_alumno');
    }
    public function getMembresiaVigenteAttribute()
    {
        $fechaActual = now()->format('Y-m-d');

        return $this->pagos()
            ->where('tipo_membresia', 'principal')
            // ->where('pag_fin', '>=', $fechaActual)

            // ->orWhere('pag_fin', '<', $fechaActual)  // Incluye vencidas
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function scopeConEstadoMembresia($query, $estado)
    {
        $hoy = now()->format('Y-m-d');
        
        // Obtenemos los IDs de los últimos pagos principales
        $ultimosPagosIds = Pagos::select(DB::raw('MAX(id_pag) as id'))
            ->where('tipo_membresia', 'principal')
            ->groupBy('fkalum')
            ->pluck('id');
        
        switch($estado) {
            case 'vigente':
                return $query->whereHas('pagos', function($q) use ($hoy, $ultimosPagosIds) {
                    $q->whereIn('id_pag', $ultimosPagosIds)
                      ->where('pag_fin', '>=', $hoy);
                });
                
            case 'por_caducar':
                $fechaLimite = now()->addDays(5)->format('Y-m-d');
                return $query->whereHas('pagos', function($q) use ($hoy, $fechaLimite, $ultimosPagosIds) {
                    $q->whereIn('id_pag', $ultimosPagosIds)
                      ->where('pag_fin', '>=', $hoy)
                      ->where('pag_fin', '<=', $fechaLimite);
                });
                
            case 'vencido':
                return $query->whereHas('pagos', function($q) use ($hoy, $ultimosPagosIds) {
                    $q->whereIn('id_pag', $ultimosPagosIds)
                      ->where('pag_fin', '<', $hoy);
                });
                
            case 'sin_membresia':
                return $query->whereDoesnthave('pagos', function($q) {
                    $q->where('tipo_membresia', 'principal');
                });
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
        return 'dia'.Carbon::parse($this->created_at)->format('d') . ' de ' . Carbon::parse($this->created_at)->locale('es')->monthName . ' del ' . Carbon::parse($this->created_at)->format('Y');
    }

    public function getAlumnoCreatedAttribute(): string
    {
        return Carbon::parse($this->created_at)->format('d/m/Y');
    }


    public function getEstadoMembresiaAttribute(): array
    {
        $pago = $this->membresiaVigente;

        if (!$pago || !$pago->pag_fin) {
            return [
                'estado' => 'Sin membresía',
                'clase'  => 'status-inactive',
                'fecha_fin' => null
            ];
        }

        $fechaFin = Carbon::parse($pago->pag_fin);
        $diferencia = now()->diffInDays($fechaFin, false);

        if ($diferencia < 0) {
            return [
                'estado' => 'Vencido',
                'clase'  => 'status-expired',
                'fecha_fin' => $fechaFin
            ];
        }

        if ($diferencia <= 5) {
            return [
                'estado' => 'Por caducar / Renovar',
                'clase'  => 'status-expiring',
                'fecha_fin' => $fechaFin
            ];
        }

        return [
            'estado' => 'Vigente',
            'clase'  => 'status-active',
            'fecha_fin' => $fechaFin
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

        return $pagoPrincipal ? $pagoPrincipal->estado_pago : 'Sin pago';

    }
    public function getClasePagoAttribute(): string
    {
        $arr = [
            'completo' => 'status-active',
            'incompleto' => 'status-expiring',
        ];

        return $arr[$this->estadoPago] ?? 'status-inactive';
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->alum_nombre.' '.$this->alum_apellido ;
    }

    public function setAlumNombreAttribute($value)
    {
        $this->attributes['alum_nombre'] = mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, "UTF-8");
    }

    public function setAlumApellidoAttribute($value)
    {
        $this->attributes['alum_apellido'] = mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, "UTF-8");
    }

}
