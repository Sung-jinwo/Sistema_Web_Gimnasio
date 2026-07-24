<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\Pago;
use App\Models\Padre;
use App\Models\Alumno;
use App\Http\Requests\AlumnoRequest;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // dd($alumnos);

        $query = Alumno::query();

        if ($request->has('search')) {
        $query->where('alum_nombre', 'like', '%' . $request->search . '%')
              ->orWhere('alum_codigo', 'like', '%' . $request->search . '%');
        }
        // condicionales
        
        
        $alumnos = $query->orderByDesc('updated_at')->paginate(10);

        return view('alumnos.index', compact('alumnos'));
        

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('alumnos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(AlumnoRequest $request)
    {
        // dd($request->all());

        $validatedData = $request->validated();

        $alum_codigo = $validatedData['alum_codigo'];

        if (Alumno::where('alum_codigo', $alum_codigo)->exists()) {
            return redirect()->back()->withErrors(['alum_codigo' => 'El código de alumno ya existe.'])->withInput();
        }
        

        $validatedData['fkuser'] = auth()->id();
        $alumno = Alumno::create($validatedData);

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // TODO: Obtener el alumno de la base de datos
        // $alumno = Alumno::findOrFail($id);
        
        return view('alumnos.show', compact('id'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $alumno = Alumno::findOrFail($id);
        
        // Si es una petición AJAX, devolver JSON
        if (request()->expectsJson()) {
            return response()->json($alumno);
        }
        
        return view('alumnos.edit', compact('alumno'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AlumnoRequest $request, $id)
    {
        try {
            // Buscar el alumno explícitamente por id_alumno
            $alumno = Alumno::findOrFail($id);
            
            $validatedData = $request->validated();
            
            // Si fkuser no viene en el request, mantener el actual
            if (!isset($validatedData['fkuser'])) {
                $validatedData['fkuser'] = $alumno->fkuser ?? auth()->id();
            }
            
            // DEBUGGING - Puedes eliminar estos logs después de que funcione
            Log::info('=== INICIO UPDATE ALUMNO ===');
            Log::info('ID recibido: ' . $id);
            Log::info('Alumno encontrado - ID: ' . $alumno->id_alumno . ', Nombre: ' . $alumno->alum_nombre);
            Log::info('Datos a actualizar:', $validatedData);
            
            // Verificar si el código está duplicado (excepto el mismo alumno)
            if (isset($validatedData['alum_codigo'])) {
                $existeCodigo = Alumno::where('alum_codigo', $validatedData['alum_codigo'])
                    ->where('id_alumno', '!=', $alumno->id_alumno)
                    ->exists();
                    
                if ($existeCodigo) {
                    Log::warning('Código duplicado: ' . $validatedData['alum_codigo']);
                    return redirect()->back()
                        ->withErrors(['alum_codigo' => 'El código de alumno ya existe.'])
                        ->withInput();
                }
            }
            
            // Actualizar el alumno
            $alumno->update($validatedData);
            
            Log::info('Alumno actualizado exitosamente - ID: ' . $alumno->id_alumno);
            Log::info('=== FIN UPDATE ALUMNO ===');
            
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Alumno actualizado exitosamente',
                    'alumno' => $alumno->fresh()
                ]);
            }
            
            return redirect()->route('alumnos.index')
                ->with('success', 'Alumno actualizado exitosamente');
                
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Alumno no encontrado con ID: ' . $id);
            
            return redirect()->route('alumnos.index')
                ->withErrors(['error' => 'El alumno no fue encontrado.']);
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar alumno - ID: ' . $id);
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el alumno: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // TODO: Eliminar el alumno de la base de datos
        // $alumno = Alumno::findOrFail($id);
        // $alumno->delete();

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno eliminado exitosamente');
    }
}
