<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PurchaseStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OrderPurchaseStatusService
{
    private const ORDERED_STATUS_MAP = [
        'telex-release'      => 'Released',
        'dated-bl'           => 'Shipped',
        'delivery-note'      => 'Delivered',
        'declaration'        => 'Customs',
        'final-loading-list' => 'In Transit',
    ];

    public function updateStatusBasedOnAttachments(Order $order): void
    {
        $allAttachmentNames = $order->attachments()->pluck('name')->map(fn($name) => strtolower($name));
        $updateData = [];
        $wasClosed = ($order->order_status === 'closed');

        $targetOrderStatus = $this->determineOrderStatus($allAttachmentNames);
        $isNowClosed = ($targetOrderStatus === 'closed');

        // In order transitioning from not-closed to closed ONLY update the order_status
        if (!$wasClosed && $isNowClosed) {
            $updateData['order_status'] = 'closed';
        }
        else {
            if ($order->order_status !== $targetOrderStatus) {
                $updateData['order_status'] = $targetOrderStatus;
            }

            $purchaseStatuses = $this->getPurchaseStatuses();
            $targetPurchaseStatusName = $this->determinePurchaseStatus($allAttachmentNames);
            $targetPurchaseStatusId = $purchaseStatuses->get($targetPurchaseStatusName);

            if ($targetPurchaseStatusId && $order->purchase_status_id !== $targetPurchaseStatusId) {
                $updateData['purchase_status_id'] = $targetPurchaseStatusId;
            }
        }

        if (!empty($updateData)) {
            Order::withoutEvents(fn() => $order->update($updateData));
        }
    }

    private function determineOrderStatus(Collection $attachmentNames): string
    {
        $hasFinalInvoice = $attachmentNames->contains(
            fn($name) => Str::contains($name, 'final-invoice')
        );

        return $hasFinalInvoice ? 'closed' : 'processing';
    }

    private function determinePurchaseStatus(Collection $attachmentNames): string
    {
        foreach (self::ORDERED_STATUS_MAP as $keyword => $statusName) {
            if ($attachmentNames->contains(fn($name) => Str::contains($name, $keyword))) {
                return $statusName;
            }
        }

        return 'Pending';
    }

    private function getPurchaseStatuses(): Collection
    {
        return PurchaseStatus::all()
            ->mapWithKeys(fn($status) => [
                trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $status->name)) => $status->id
            ]);
    }
}
