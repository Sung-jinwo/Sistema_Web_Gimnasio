<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlumnoRequest;
use App\Models\Alumno;
use App\Models\Sede;
use App\Models\Sexo;
use App\Services\AlumnoService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    protected AlumnoService $alumnoService;

    protected AuditService $auditService;

    public function __construct(AlumnoService $alumnoService, AuditService $auditService)
    {
        $this->alumnoService = $alumnoService;
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Alumno::class);

        $filtros = [
            'search' => $request->input('search'),
            'sede' => $request->input('sede'),
            'estado' => $request->input('estado'),
        ];

        $alumnos = $this->alumnoService->obtenerAlumnosConFiltros($filtros, auth()->user());
        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();
        $sexos = Sexo::orderBy('sexo_nombre')->get();

        if ($request->expectsJson()) {
            return response()->json($alumnos);
        }

        return view('alumnos.index', compact('alumnos', 'sedes', 'sexos'));
    }

    public function store(AlumnoRequest $request)
    {
        $this->authorize('create', Alumno::class);

        $validatedData = $request->validated();
        $validatedData['fkuser'] = auth()->id();
        $validatedData['alum_estado'] = true;

        $alumno = Alumno::create($validatedData);

        $this->auditService->registrarCreacion(
            'alumnos',
            'Alumno',
            $alumno->id_alumno,
            $alumno->toArray()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alumno creado exitosamente',
                'alumno' => $alumno,
            ], 201);
        }

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno creado exitosamente');
    }

    public function show($id)
    {
        $alumno = Alumno::findOrFail($id);
        $this->authorize('view', $alumno);

        $ficha = $this->alumnoService->obtenerFichaCompleta($id);

        if (request()->expectsJson()) {
            return response()->json($ficha);
        }

        return view('alumnos.show', $ficha);
    }

    public function edit($id)
    {
        $alumno = Alumno::findOrFail($id);
        $this->authorize('update', $alumno);

        if (request()->expectsJson()) {
            return response()->json($alumno);
        }

        $sedes = Sede::where('sede_estado', true)->orderBy('sede_nombre')->get();

        return view('alumnos.edit', compact('alumno', 'sedes'));
    }

    public function update(AlumnoRequest $request, $id)
    {
        $alumno = Alumno::findOrFail($id);
        $this->authorize('update', $alumno);

        $valoresAntiguos = $alumno->toArray();

        $validatedData = $request->validated();

        if (! isset($validatedData['fkuser'])) {
            $validatedData['fkuser'] = $alumno->fkuser ?? auth()->id();
        }

        if ($request->exists('alum_estado')) {
            $validatedData['alum_estado'] = $request->boolean('alum_estado');
        }

        $alumno->update($validatedData);

        $this->auditService->registrarEdicion(
            'alumnos',
            'Alumno',
            $alumno->id_alumno,
            $valoresAntiguos,
            $alumno->fresh()->toArray()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alumno actualizado exitosamente',
                'alumno' => $alumno->fresh(),
            ]);
        }

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno actualizado exitosamente');
    }

    public function destroy(Request $request, $id)
    {
        $alumno = Alumno::findOrFail($id);
        $this->authorize('delete', $alumno);

        $valoresAntiguos = $alumno->toArray();

        $this->auditService->registrarEliminacion(
            'alumnos',
            'Alumno',
            $alumno->id_alumno,
            $valoresAntiguos
        );

        $alumno->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alumno eliminado exitosamente',
            ]);
        }

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno eliminado exitosamente');
    }
}
