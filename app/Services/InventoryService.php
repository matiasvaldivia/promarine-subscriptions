<?php

namespace App\Services;

use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Stock disponible = available - reserved - committed
     */
    public function availableStock(int $variantId, int $locationId): int
    {
        $level = InventoryLevel::where('variant_id', $variantId)
                               ->where('location_id', $locationId)
                               ->first();

        if (!$level) return 0;

        return max(0, $level->available_quantity - $level->reserved_quantity - $level->committed_quantity);
    }

    /**
     * Ajuste manual de stock (admin)
     */
    public function adjust(InventoryLevel $level, int $delta, string $reason = 'Ajuste manual'): void
    {
        DB::transaction(function () use ($level, $delta, $reason) {
            $level->increment('available_quantity', $delta);
            $level->recalculateSyncStatus();

            InventoryMovement::create([
                'variant_id'     => $level->variant_id,
                'location_id'    => $level->location_id,
                'type'           => 'adjustment',
                'quantity'       => $delta,
                'reason'         => $reason,
                'metadata_json'  => ['source' => 'admin_panel'],
            ]);
        });
    }

    /**
     * Reservar stock para un pedido
     */
    public function reserve(int $variantId, int $locationId, int $quantity, string $refType, int $refId): InventoryReservation
    {
        return DB::transaction(function () use ($variantId, $locationId, $quantity, $refType, $refId) {
            $level = InventoryLevel::where('variant_id', $variantId)
                                   ->where('location_id', $locationId)
                                   ->lockForUpdate()
                                   ->first();

            if (!$level || $this->availableStock($variantId, $locationId) < $quantity) {
                throw new \RuntimeException("Stock insuficiente para variante {$variantId}");
            }

            $level->increment('reserved_quantity', $quantity);
            $level->recalculateSyncStatus();

            InventoryMovement::create([
                'variant_id'     => $variantId,
                'location_id'    => $locationId,
                'type'           => 'reservation',
                'quantity'       => -$quantity,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'reason'         => "Reserva para {$refType} #{$refId}",
            ]);

            return InventoryReservation::create([
                'variant_id'     => $variantId,
                'location_id'    => $locationId,
                'quantity'       => $quantity,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'status'         => 'active',
            ]);
        });
    }

    /**
     * Liberar reserva
     */
    public function release(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $level = InventoryLevel::where('variant_id', $reservation->variant_id)
                                   ->where('location_id', $reservation->location_id)
                                   ->lockForUpdate()
                                   ->first();

            if ($level) {
                $level->decrement('reserved_quantity', $reservation->quantity);
                $level->recalculateSyncStatus();
            }

            $reservation->update(['status' => 'released']);

            InventoryMovement::create([
                'variant_id'     => $reservation->variant_id,
                'location_id'    => $reservation->location_id,
                'type'           => 'release',
                'quantity'       => $reservation->quantity,
                'reference_type' => $reservation->reference_type,
                'reference_id'   => $reservation->reference_id,
                'reason'         => 'Reserva liberada',
            ]);
        });
    }

    public function summary(): array
    {
        return [
            'in_stock'    => InventoryLevel::inStock()->count(),
            'low_stock'   => InventoryLevel::lowStock()->count(),
            'out_of_stock'=> InventoryLevel::outOfStock()->count(),
        ];
    }
}
