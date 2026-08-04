<?php

namespace App\Services;

use App\Models\ProductSubscriptionMatrix;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection;

class CartMatrixService
{
    public function all(): Collection
    {
        return ProductSubscriptionMatrix::with(['product', 'variant', 'plan'])
                                        ->orderBy('product_id')
                                        ->orderBy('variant_id')
                                        ->get();
    }

    public function forProduct(int $productId): Collection
    {
        return ProductSubscriptionMatrix::with(['variant', 'plan'])
                                        ->where('product_id', $productId)
                                        ->active()
                                        ->get();
    }

    public function forVariant(int $variantId): Collection
    {
        return ProductSubscriptionMatrix::with(['product', 'plan'])
                                        ->where('variant_id', $variantId)
                                        ->active()
                                        ->get();
    }

    public function updateRow(ProductSubscriptionMatrix $row, array $data): ProductSubscriptionMatrix
    {
        // Recalcular subscription_price si cambian base_price o discount
        if (isset($data['base_price']) || isset($data['discount_value']) || isset($data['discount_type'])) {
            $basePrice    = $data['base_price']    ?? $row->base_price;
            $discountType = $data['discount_type'] ?? $row->discount_type;
            $discountVal  = $data['discount_value']?? $row->discount_value;

            $data['subscription_price'] = $discountType === 'percentage'
                ? round($basePrice * (1 - $discountVal / 100), 2)
                : max(0, $basePrice - $discountVal);
        }

        $row->update($data);
        return $row->fresh();
    }

    public function summary(): array
    {
        $total  = ProductSubscriptionMatrix::count();
        $active = ProductSubscriptionMatrix::active()->count();
        return ['total' => $total, 'active' => $active, 'inactive' => $total - $active];
    }
}
