<?php

namespace App\Services;

use App\Models\MembresiaAlumno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReporteMensualService
{
    public const ALCANCE_TODOS = 'todos';

    public const ALCANCE_VENCEN = 'vencen';

    /**
     * Columnas permitidas (lista cerrada, sin datos financieros).
     */
    public const COLUMNAS = [
        'codigo' => 'Código',
        'dni' => 'DNI',
        'nombres' => 'Nombres',
        'apellidos' => 'Apellidos',
        'telefono' => 'Teléfono',
        'correo' => 'Correo',
        'sexo' => 'Sexo',
        'edad' => 'Edad',
        'fecha_registro' => 'Fecha de registro',
        'estado_alumno' => 'Estado del alumno',
        'plan' => 'Plan',
        'inicio' => 'Inicio',
        'vencimiento' => 'Vencimiento',
        'estado_membresia' => 'Estado de membresía',
    ];

    public static function columnas(): array
    {
        return self::COLUMNAS;
    }

    /**
     * Años existentes en membresías, garantizando el año actual.
     */
    public function aniosDisponibles(): array
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $anioInicio = $driver === 'sqlite'
            ? "CAST(strftime('%Y', fecha_inicio) AS INTEGER)"
            : 'YEAR(fecha_inicio)';
        $anioFin = $driver === 'sqlite'
            ? "CAST(strftime('%Y', fecha_fin) AS INTEGER)"
            : 'YEAR(fecha_fin)';

        $anios = MembresiaAlumno::selectRaw("DISTINCT {$anioInicio} as anio")
            ->pluck('anio')
            ->merge(
                MembresiaAlumno::selectRaw("DISTINCT {$anioFin} as anio")->pluck('anio')
            )
            ->filter()
            ->map(fn ($a) => (int) $a)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return $anios === [] ? [(int) now()->year] : $anios;
    }

    /**
     * Valida los filtros con lista cerrada de campos.
     *
     * @throws ValidationException
     */
    public function validar(array $input): array
    {
        $validator = Validator::make($input, [
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2000|max:2100',
            'alcance' => 'required|in:'.self::ALCANCE_TODOS.','.self::ALCANCE_VENCEN,
            'columnas' => 'required|array|min:1',
            'columnas.*' => 'in:'.implode(',', array_keys(self::COLUMNAS)),
            'sede' => 'nullable|integer|exists:sedes,id_sede',
            'registrador' => 'nullable|integer|exists:users,id',
        ], [
            'mes.required' => 'Debe seleccionar el mes.',
            'anio.required' => 'Debe seleccionar el año.',
            'alcance.in' => 'Alcance no válido.',
            'columnas.required' => 'Debe seleccionar al menos una columna.',
            'columnas.min' => 'Debe seleccionar al menos una columna.',
            'columnas.*.in' => 'Columna no permitida.',
        ]);

        return $validator->validate();
    }

    /**
     * Query base con el alcance impuesto por el backend según el rol.
     * Una fila por membresía (las renovaciones del mismo mes generan varias filas).
     */
    public function construirQuery(User $user, array $filtros)
    {
        $mes = (int) $filtros['mes'];
        $anio = (int) $filtros['anio'];
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth()->endOfDay();

        $query = MembresiaAlumno::with(['alumno.sexo', 'membresia']);

        if ($filtros['alcance'] === self::ALCANCE_VENCEN) {
            $query->whereYear('fecha_fin', $anio)->whereMonth('fecha_fin', $mes);
        } else {
            // Vigente al menos un día del periodo (incluye las que cruzan los límites).
            $query->whereDate('fecha_inicio', '<=', $finMes->toDateString())
                ->whereDate('fecha_fin', '>=', $inicioMes->toDateString());
        }

        if ($user->hasRole('Administrador')) {
            if (! empty($filtros['sede'])) {
                $query->whereHas('alumno', fn ($q) => $q->where('fksede', $filtros['sede']));
            }
            if (! empty($filtros['registrador'])) {
                $query->whereHas('alumno', fn ($q) => $q->where('fkuser', $filtros['registrador']));
            }
        } else {
            // Local y Redes: solo alumnos registrados por su propio usuario, sin excepción.
            $query->whereHas('alumno', fn ($q) => $q->where('fkuser', $user->id));
        }

        return $query->orderBy('fecha_fin')->orderBy('id_membresia_alumno');
    }

    /**
     * Mapea una membresía a una fila con exactamente las columnas pedidas.
     */
    public function mapearFila(MembresiaAlumno $membresia, array $columnas): array
    {
        $alumno = $membresia->alumno;
        $fila = [];

        foreach ($columnas as $columna) {
            $fila[$columna] = match ($columna) {
                'codigo' => $alumno?->alum_codigo ?? '-',
                'dni' => $alumno?->alum_numDoc ?? '-',
                'nombres' => $alumno?->alum_nombre ?? '-',
                'apellidos' => $alumno?->alum_apellido ?? '-',
                'telefono' => $alumno?->alum_telefo ?? '-',
                'correo' => $alumno?->alum_correro ?? '-',
                'sexo' => $alumno?->sexo?->sexo_nombre ?? '-',
                'edad' => $alumno ? $this->edad($alumno) : '-',
                'fecha_registro' => $alumno?->created_at?->format('d/m/Y') ?? '-',
                'estado_alumno' => $alumno ? ($alumno->alum_estado ? 'Activo' : 'Inactivo') : '-',
                'plan' => $membresia->membresia?->mem_nombre ?? '-',
                'inicio' => $membresia->fecha_inicio ? Carbon::parse($membresia->fecha_inicio)->format('d/m/Y') : '-',
                'vencimiento' => $membresia->fecha_fin ? Carbon::parse($membresia->fecha_fin)->format('d/m/Y') : '-',
                'estado_membresia' => $membresia->estado_formato,
                default => '-',
            };
        }

        return $fila;
    }

    public function encabezados(array $columnas): array
    {
        return array_map(fn ($c) => self::COLUMNAS[$c] ?? $c, $columnas);
    }

    private function edad($alumno): int|string
    {
        if (empty($alumno->fecha_nac)) {
            return '-';
        }

        return Carbon::parse($alumno->fecha_nac)->age;
    }
}
