<?php

namespace App\Services;

use App\Models\MockOrder;
use App\Models\OrderStatusHistory;
use App\Models\User;

/**
 * Máquina de estados para pedidos mock.
 * Rechaza transiciones inválidas lanzando InvalidTransitionException.
 */
class OrderStateMachine
{
    /**
     * Mapa de transiciones válidas: from → [allowed tos]
     */
    private const TRANSITIONS = [
        'draft'                 => ['pending_payment', 'cancelled'],
        'pending_payment'       => ['payment_approved', 'payment_rejected', 'cancelled'],
        'payment_rejected'      => ['cancelled'],  // NUNCA → transmitted
        'payment_approved'      => ['ready_to_transmit', 'cancelled'],
        'ready_to_transmit'     => ['transmitting', 'cancelled'],
        'transmitting'          => ['transmitted', 'sync_error', 'cancelled'],
        'sync_error'            => ['ready_to_transmit', 'cancelled'],  // recuperable
        'transmitted'           => ['confirmed_by_shopify', 'cancelled'],
        'confirmed_by_shopify'  => ['preparing', 'cancelled'],
        'preparing'             => ['ready_to_ship', 'cancelled'],
        'ready_to_ship'         => ['shipped'],
        'shipped'               => ['delivered', 'returned'],
        'returned'              => ['cancelled'],
        'delivered'             => [],  // terminal — no permite retroceso
        'cancelled'             => [],  // terminal
    ];

    public function canTransition(MockOrder $order, string $to): bool
    {
        $from    = $order->internal_status ?? 'draft';
        $allowed = self::TRANSITIONS[$from] ?? [];
        return in_array($to, $allowed);
    }

    /**
     * @throws \InvalidArgumentException cuando la transición no está permitida
     */
    public function transition(MockOrder $order, string $to, ?User $actor = null, ?string $reason = null): void
    {
        $from = $order->internal_status ?? 'draft';

        if (!$this->canTransition($order, $to)) {
            throw new \InvalidArgumentException(
                "Transición inválida: [{$from}] → [{$to}]. No permitida por la máquina de estados."
            );
        }

        $timestamps = $this->timestampsFor($to);

        $order->update(array_merge(
            ['internal_status' => $to],
            $timestamps
        ));

        OrderStatusHistory::create([
            'mock_order_id' => $order->id,
            'from_status'   => $from,
            'to_status'     => $to,
            'changed_by'    => $actor?->id,
            'reason'        => $reason ?? "Transición automática: {$from} → {$to}",
        ]);
    }

    public function allowedFrom(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    private function timestampsFor(string $status): array
    {
        return match($status) {
            'transmitted'          => ['transmitted_at' => now()],
            'confirmed_by_shopify' => ['confirmed_at'   => now()],
            'shipped'              => ['dispatched_at'  => now()],
            'delivered'            => ['delivered_at'   => now()],
            'cancelled'            => ['cancelled_at'   => now()],
            default                => [],
        };
    }
}
