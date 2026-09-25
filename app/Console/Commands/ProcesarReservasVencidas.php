<?php

namespace App\Console\Commands;

use App\Models\Venta;
use App\Services\CommissionService;
use App\Services\InventoryReservationService;
use App\Services\PaymentService;
use Illuminate\Console\Command;

class ProcesarReservasVencidas extends Command
{
    protected $signature = 'ventas:procesar-vencimientos';

    protected $description = 'Marca deudas vencidas, libera stock reservado y actualiza penalizaciones';

    public function handle(InventoryReservationService $inventario, PaymentService $pagos, CommissionService $comisiones): int
    {
        $pagos->marcarComoVencido();
        $liberadas = 0;

        Venta::query()
            ->where('tipo_venta', 'producto')
            ->where('saldo', '>', 0)
            ->whereNull('stock_liberado_at')
            ->where('estado_venta', '!=', 'anulado')
            ->whereDate('fecha_acordada', '<', today())
            ->orderBy('id_venta')
            ->each(function (Venta $venta) use ($inventario, &$liberadas) {
                $liberadas += $inventario->liberarReservaVencida($venta) ? 1 : 0;
            });

        $actualizadas = $comisiones->actualizarPenalizacionesPendientes();
        $this->info("Reservas liberadas: {$liberadas}. Comisiones actualizadas: {$actualizadas}.");

        return self::SUCCESS;
    }
}
