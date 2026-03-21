<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerNotification;
use App\Models\User;

class InventoryService
{
    public function syncStatusAndAlert(Product $product, ?User $user = null, bool $notify = true): Product
    {
        if (! $product->isInventoryManaged()) {
            return $product;
        }

        $newStatus = $this->deriveStatus($product->stock_quantity, $product->low_stock_threshold);
        $changed = false;

        if ($product->status !== $newStatus) {
            $product->status = $newStatus;
            $changed = true;
        }

        if ($newStatus === Product::STATUS_IN_STOCK) {
            if ($product->stock_alert_state !== null || $product->stock_alerted_at !== null) {
                $product->stock_alert_state = null;
                $product->stock_alerted_at = null;
                $changed = true;
            }
        } elseif ($notify && $product->stock_alert_state !== $newStatus) {
            $product->stock_alert_state = $newStatus;
            $product->stock_alerted_at = now();
            $changed = true;

            $targetUser = $user ?? $product->user;
            if ($targetUser) {
                $title = $newStatus === Product::STATUS_SOLD_OUT ? 'Product sold out' : 'Low stock alert';
                $body = $newStatus === Product::STATUS_SOLD_OUT
                    ? $product->name.' is now sold out.'
                    : $product->name.' is low on stock ('.$product->stock_quantity.' left).';

                app(SellerNotifier::class)->notify(
                    $targetUser,
                    SellerNotification::TYPE_STOCK_ALERT,
                    $title,
                    $body,
                    [
                        'product_id' => $product->id,
                        'status' => $newStatus,
                        'stock_quantity' => $product->stock_quantity,
                        'low_stock_threshold' => $product->low_stock_threshold,
                    ],
                    sendEmail: false,
                    sendWhatsApp: true
                );
            }
        }

        if ($changed) {
            $product->save();
        }

        return $product;
    }

    public function decrementForDirectProductPayment(Product $product, int $quantity = 1, ?User $user = null): Product
    {
        if (! $product->isInventoryManaged()) {
            return $product;
        }

        $product->stock_quantity = max(0, (int) $product->stock_quantity - max(1, $quantity));
        $product->save();

        return $this->syncStatusAndAlert($product, $user, true);
    }

    public function decrementForOrder(Order $order, ?User $user = null): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $this->decrementForDirectProductPayment($product, (int) $item->quantity, $user);
        }
    }

    private function deriveStatus(int $stockQuantity, int $threshold): string
    {
        if ($stockQuantity <= 0) {
            return Product::STATUS_SOLD_OUT;
        }

        if ($stockQuantity <= max(0, $threshold)) {
            return Product::STATUS_LOW_STOCK;
        }

        return Product::STATUS_IN_STOCK;
    }
}
