<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            'alumnos.ver',
            'alumnos.crear',
            'alumnos.editar',
            'alumnos.eliminar',

            'membresias.ver',
            'membresias.crear',
            'membresias.editar',

            'productos.ver',
            'productos.crear',
            'productos.editar',

            'ventas.ver',
            'ventas.crear',

            'pagos.ver',
            'pagos.crear',

            'gastos.ver',
            'gastos.crear',
            'gastos.aprobar',

            'caja.ver',
            'caja.abrir',
            'caja.cerrar',

            'comisiones.ver',

            'reportes.ver',

            'seguimiento.ver',

            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            'sedes.ver',
            'sedes.crear',
            'sedes.editar',

            'asistencias.ver',
            'asistencias.crear',

            'notificaciones.ver',

            'auditoria.ver',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $local = Role::firstOrCreate(['name' => 'Local', 'guard_name' => 'web']);
        $local->syncPermissions([
            'alumnos.ver', 'alumnos.crear', 'alumnos.editar',
            'membresias.ver', 'membresias.crear', 'membresias.editar',
            'productos.ver', 'productos.crear', 'productos.editar',
            'ventas.ver', 'ventas.crear',
            'pagos.ver', 'pagos.crear',
            'gastos.ver', 'gastos.crear',
            'caja.ver', 'caja.abrir', 'caja.cerrar',
            'comisiones.ver',
            'reportes.ver',
            'seguimiento.ver',
            'asistencias.ver', 'asistencias.crear',
            'notificaciones.ver',
        ]);

        $redes = Role::firstOrCreate(['name' => 'Redes', 'guard_name' => 'web']);
        $redes->syncPermissions([
            'alumnos.ver', 'alumnos.crear', 'alumnos.editar',
            'membresias.ver', 'membresias.crear', 'membresias.editar',
            'seguimiento.ver',
            'asistencias.ver', 'asistencias.crear',
            'notificaciones.ver',
        ]);

        $asistencia = Role::firstOrCreate(['name' => 'Asistencia', 'guard_name' => 'web']);
        $asistencia->syncPermissions([
            'alumnos.ver',
            'asistencias.ver', 'asistencias.crear',
            'notificaciones.ver',
        ]);

        $this->migrarUsuarios();
    }

    private function migrarUsuarios(): void
    {
        $mapeo = [
            User::ROL_ADMIN => 'Administrador',
            User::ROL_EMPLEADO => 'Local',
            User::ROL_VENTAS => 'Redes',
            User::ROL_ASISTENCIA => 'Asistencia',
        ];

        $usuarios = User::all();
        foreach ($usuarios as $usuario) {
            if (isset($mapeo[$usuario->rol])) {
                $usuario->assignRole($mapeo[$usuario->rol]);
            }
        }
    }
}
