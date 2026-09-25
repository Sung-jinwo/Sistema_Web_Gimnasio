<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    public function test_active_submenu_parent_is_expanded_on_page_load(): void
    {
        $cases = [
            'cobranza.vencidas' => 'cobranza',
            'reportes.ventas' => 'reportes',
        ];

        foreach ($cases as $routeName => $parentMenu) {
            $uri = '/sidebar-test/'.str_replace('.', '-', $routeName);

            Route::get($uri, fn () => Blade::render('<x-sidebar />'))
                ->name($routeName);

            $response = $this->get($uri);

            $response->assertOk();
            $response->assertSee("\\u0022{$parentMenu}\\u0022:true", false);
            $response->assertSee("toggleSubmenu('{$parentMenu}')", false);
        }
    }

    public function test_page_without_submenu_does_not_expand_any_parent_menu(): void
    {
        Route::get('/sidebar-test/alumnos', fn () => Blade::render('<x-sidebar />'))
            ->name('alumnos.index');

        $response = $this->get('/sidebar-test/alumnos');

        $response->assertOk();
        $response->assertDontSee('\\u0022ventas\\u0022:true', false);
        $response->assertDontSee('\\u0022cobranza\\u0022:true', false);
        $response->assertDontSee('\\u0022reportes\\u0022:true', false);
    }
}
