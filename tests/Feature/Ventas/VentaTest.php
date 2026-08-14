<?php

namespace Tests\Feature\Ventas;

use App\Models\Alumno;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        
        MetodoPago::firstOrCreate(['id_metod' => 1], ['metod_nombre' => 'Efectivo']);
        MetodoPago::firstOrCreate(['id_metod' => 2], ['metod_nombre' => 'Tarjeta']);
    }

    public function test_can_create_product_sale(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_cantidad' => 10,
            'prod_precio' => 50.00,
        ]);

        $data = [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos,
            'cantidad' => 2,
            'fkmetodo' => 1,
            'estado_venta' => 'completado',
            'estado_pago' => 'pagado',
        ];

        $response = $this->actingAs($admin)->post('/ventas', $data);

        $response->assertRedirect('/ventas');
        $this->assertDatabaseHas('ventas', [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
        ]);
    }

    public function test_stock_is_decremented_after_sale(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_cantidad' => 10,
            'prod_precio' => 50.00,
        ]);

        $data = [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos,
            'cantidad' => 3,
            'fkmetodo' => 1,
            'estado_venta' => 'completado',
            'estado_pago' => 'pagado',
        ];

        $this->actingAs($admin)->post('/ventas', $data);

        $this->assertDatabaseHas('productos', [
            'id_productos' => $producto->id_productos,
            'prod_cantidad' => 7,
        ]);
    }

    public function test_cannot_sell_more_than_available_stock(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_cantidad' => 5,
            'prod_precio' => 50.00,
        ]);

        $data = [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos,
            'cantidad' => 10,
            'fkmetodo' => 1,
            'estado_venta' => 'completado',
            'estado_pago' => 'pagado',
        ];

        $response = $this->actingAs($admin)->post('/ventas', $data);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('productos', [
            'id_productos' => $producto->id_productos,
            'prod_cantidad' => 5,
        ]);
    }

    public function test_can_create_quick_sale_without_student(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_cantidad' => 10,
            'prod_precio' => 10.00,
        ]);

        $data = [
            'tipo_venta' => 'rapida',
            'fkproducto' => $producto->id_productos,
            'cantidad' => 1,
            'fkmetodo' => 1,
        ];

        $response = $this->actingAs($admin)->post('/ventas', $data);

        $response->assertRedirect('/ventas');
        $this->assertDatabaseHas('ventas', [
            'tipo_venta' => 'rapida',
            'fkalum' => null,
        ]);
    }

    public function test_commission_is_created_after_sale(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_cantidad' => 10,
            'prod_precio' => 100.00,
        ]);

        $data = [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos,
            'cantidad' => 1,
            'fkmetodo' => 1,
            'estado_venta' => 'completado',
            'estado_pago' => 'pagado',
        ];

        $this->actingAs($admin)->post('/ventas', $data);

        $this->assertDatabaseHas('comisiones', [
            'fkuser' => $admin->id,
            'tipo' => 'venta',
        ]);
    }

    public function test_redes_user_cannot_create_sales(): void
    {
        $sede = Sede::factory()->create();
        $redes = User::factory()->create(['fksede' => $sede->id_sede]);
        $redes->assignRole('Redes');

        $alumno = Alumno::factory()->create(['fksede' => $sede->id_sede]);
        $producto = Producto::factory()->create([
            'fksede' => $sede->id_sede,
            'prod_cantidad' => 10,
        ]);

        $data = [
            'tipo_venta' => 'producto',
            'fkalum' => $alumno->id_alumno,
            'fkproducto' => $producto->id_productos,
            'cantidad' => 1,
            'fkmetodo' => 1,
        ];

        $response = $this->actingAs($redes)->post('/ventas', $data);

        $response->assertStatus(403);
    }

    public function test_can_view_sales_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        Venta::factory()->count(5)->create(['fksede' => $sede->id_sede]);

        $response = $this->actingAs($admin)->get('/ventas');

        $response->assertStatus(200);
    }

    public function test_can_filter_sales_by_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $sede = Sede::factory()->create();
        Venta::factory()->count(3)->create(['fksede' => $sede->id_sede, 'tipo_venta' => 'producto']);
        Venta::factory()->count(2)->create(['fksede' => $sede->id_sede, 'tipo_venta' => 'rapida']);

        $response = $this->actingAs($admin)->get('/ventas?tipo_venta=producto');

        $response->assertStatus(200);
    }
}
