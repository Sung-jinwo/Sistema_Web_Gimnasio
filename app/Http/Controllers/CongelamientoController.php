<?php

namespace App\Http\Controllers;

use App\Services\CongelamientoService;
use Illuminate\Http\Request;

class CongelamientoController extends Controller
{
    public function __construct(private readonly CongelamientoService $vigencia) {}

    public function programar(Request $request, $membresiaAlumno)
    {
        $data = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'required|string|max:1000',
        ], [
            'fecha_inicio.required' => 'Debe indicar la fecha inicial.',
            'fecha_fin.required' => 'Debe indicar la fecha final.',
            'motivo.required' => 'Debe indicar el motivo.',
        ]);

        try {
            $this->vigencia->programar((int) $membresiaAlumno, $data['fecha_inicio'], $data['fecha_fin'], $data['motivo'], auth()->id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($request, $e->getMessage());
        }

        return $this->ok($request, 'Congelamiento registrado y vencimiento extendido.');
    }

    public function finalizar(Request $request, $congelamiento)
    {
        try {
            $this->vigencia->finalizarAnticipado((int) $congelamiento, auth()->id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($request, $e->getMessage());
        }

        return $this->ok($request, 'Congelamiento finalizado. Se conservaron solo los días utilizados.');
    }

    public function cancelar(Request $request, $congelamiento)
    {
        try {
            $this->vigencia->cancelarProgramado((int) $congelamiento, auth()->id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($request, $e->getMessage());
        }

        return $this->ok($request, 'Programación cancelada y extensión revertida.');
    }

    public function ajustar(Request $request, $membresiaAlumno)
    {
        $data = $request->validate([
            'fecha_nueva' => 'required|date',
            'motivo' => 'required|string|max:1000',
        ], [
            'fecha_nueva.required' => 'Debe indicar la nueva fecha final.',
            'motivo.required' => 'Debe indicar el motivo del ajuste.',
        ]);

        try {
            $this->vigencia->ajustarVencimiento((int) $membresiaAlumno, $data['fecha_nueva'], $data['motivo'], auth()->id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($request, $e->getMessage());
        }

        return $this->ok($request, 'Vencimiento ajustado correctamente.');
    }

    protected function ok(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje]);
        }

        return back()->with('success', $mensaje);
    }

    protected function error(Request $request, string $mensaje)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $mensaje], 422);
        }

        return back()->withErrors(['error' => $mensaje]);
    }
}
