<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PurchaseStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OrderPurchaseStatusService
{
    private const ORDERED_STATUS_MAP = [
        'telex-release' => 'Released',
        'dated-bl' => 'Shipped',
        'delivery-note' => 'Delivered',
        'declaration' => 'Customs',
        'final-loading-list' => 'In Transit',
    ];

    private const PROTECTED_STATUSES = [
        'cancelled',
        'accounting_review',
        'accounting_approved',
        'accounting_rejected',
    ];
    private static ?Collection $purchaseStatusesCache = null;

    public function updateStatusBasedOnAttachments(Order $order): void
    {
        if (in_array($order->order_status, self::PROTECTED_STATUSES, true) || !$order->attachments()->exists()) {
            return;
        }

        $order->loadMissing('doc');
        $attachments = $order->relationLoaded('attachments')
            ? $order->attachments
            : $order->attachments()->select('name')->get();


        $isClosed = $this->determineOrderStatus($order, $attachments);
        $updateData = ['order_status' => $isClosed ? 'closed' : 'processing'];

        if (!$isClosed) {
            $purchaseStatuses = $this->getPurchaseStatuses();
            $targetPurchaseStatusName = $this->determinePurchaseStatus($attachments);
            $targetPurchaseStatusId = $purchaseStatuses->get($targetPurchaseStatusName);

            if ($targetPurchaseStatusId && $order->purchase_status_id !== $targetPurchaseStatusId) {
                $updateData['purchase_status_id'] = $targetPurchaseStatusId;
            }
        }

        if (!empty($updateData)) {
            Order::withoutEvents(fn() => $order->update($updateData));
        }
    }

    private function determineOrderStatus(Order $order, Collection $attachments): bool
    {
        return !empty($order->doc?->BL_date) && $attachments->contains(fn($att) => Str::contains(mb_strtolower($att->name ?? ''), 'final-invoice'));
    }

    private function determinePurchaseStatus(Collection $attachments): string
    {
        foreach (self::ORDERED_STATUS_MAP as $keyword => $statusName) {
            if ($attachments->contains(fn($att) => Str::contains(mb_strtolower($att->name ?? ''), $keyword))) {
                return $statusName;
            }
        }
        return 'Pending';
    }

    private function getPurchaseStatuses(): Collection
    {
        if (self::$purchaseStatusesCache === null) {
            self::$purchaseStatusesCache = Cache::remember('purchase_statuses', 86400,
                fn() => PurchaseStatus::all()->mapWithKeys(fn($status) => [
                    trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $status->name)) => $status->id
                ])
            );
        }
        return self::$purchaseStatusesCache;
    }
}
