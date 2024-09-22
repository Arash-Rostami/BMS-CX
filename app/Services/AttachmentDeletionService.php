<?php

namespace App\Services;

use App\Models\Attachment;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\HtmlString;

class AttachmentDeletionService
{
    public array $relatedReferenceNumbers = [];
    protected array $relationMappings = [
        'proformaInvoice' => [
            'exclude_field' => 'proforma_invoice_id',
            'entity_name' => 'Proforma Invoice',
        ],
        'order' => [
            'exclude_field' => 'order_id',
            'entity_name' => 'Order',
        ],
        'paymentRequest' => [
            'exclude_field' => 'payment_request_id',
            'entity_name' => 'Payment Request',
        ]
    ];

    public function __construct(public Attachment $attachment, public $record)
    {
    }

    public static function validateAttachmentExists(Repeater $component, $item, $operation, Action $action, $record)
    {
        $itemData = $component->getItemState($item);

        if ($operation != 'edit' || $itemData['file_path'] == null) {
            return new HtmlString("<span>Of course, no file found!</span>");
        }

        if ($operation != 'edit') {
            return new HtmlString("<span>Attachment not found.</span>");
        }

        $attachment = Attachment::find($itemData['id']);

        if (!$attachment) {
            return new HtmlString("<span>Attachment not found.</span>");
        }

        $attachmentService = new AttachmentDeletionService($attachment, $record);

        return $attachmentService->generateDeletionConfirmationMessage();
    }

    public static function removeAttachment(Repeater $component, $item): void
    {
        $allItems = $component->getState();

        $itemIdToRemove = $item;

        if (!isset($allItems[$itemIdToRemove])) return;

        $attachment = Attachment::find($allItems[$itemIdToRemove]['id']);

        if ($attachment) {
            $attachment->delete();
        }

        unset($allItems[$itemIdToRemove]);
        $component->state(array_values($allItems));
    }

    public function generateDeletionConfirmationMessage()
    {
        $allReferenceNumbers = [];
        $relations = ['proformaInvoice', 'order', 'paymentRequest'];

        $relatedAttachments = Attachment::getRelatedRecords($this->attachment, $relations);

        $allReferenceNumbers = $this->collectRelatedReferenceNumbers($relations, $relatedAttachments, $allReferenceNumbers);

        if (!empty($allReferenceNumbers)) {
            $fullMessage = implode('<br><br>', $allReferenceNumbers);
            $fullMessage .= "<br><br>Are you still sure you'd like to delete it?";
            return new HtmlString($fullMessage);
        }

        return "Are you sure you'd like to delete this attachment?";
    }

    protected function collectRelatedReferenceNumbers(array $relations, \Illuminate\Database\Eloquent\Collection|array|\Illuminate\Support\Collection $relatedAttachments, array $allReferenceNumbers): array
    {
        foreach ($relations as $relation) {

            $mapping = $this->relationMappings[$relation];
            $this->relatedReferenceNumbers = [];

            foreach ($relatedAttachments as $relatedAttachment) {
                if ($relatedAttachment->$relation) {
                    $this->relatedReferenceNumbers[] = $relatedAttachment->$relation->reference_number;
                }
            }

            $uniqueReferenceNumbers = array_unique($this->relatedReferenceNumbers);

            if (!empty($uniqueReferenceNumbers)) {
                $referenceNumbersList = implode(', ', $uniqueReferenceNumbers);
                $allReferenceNumbers[] = "This attachment is used in the following {$mapping['entity_name']}(s):<br>{$referenceNumbersList}.";
            }
        }
        return $allReferenceNumbers;
    }
}
