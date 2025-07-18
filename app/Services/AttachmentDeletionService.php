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

        $data = $component->getItemState($item);

        if ($operation != 'edit') {
            return new HtmlString('<span>Attachment not found.</span>');
        }

        if (empty($data['file_path'])) {
            return new HtmlString('<span>Of course, no file found!</span>');
        }

        $attachment = Attachment::find($data['id']);

        if (!$attachment) {
            return new HtmlString('<span>Attachment not found.</span>');
        }

        return (new AttachmentDeletionService($attachment, $record))
            ->generateDeletionConfirmationMessage();
    }

    public function generateDeletionConfirmationMessage()
    {
        $refs = [];
        $relations = ['proformaInvoice', 'order', 'paymentRequest'];
        $related = Attachment::getRelatedRecords($this->attachment, $relations);
        $refs = $this->collectRelatedReferenceNumbers($relations, $related, $refs);

        if (!empty($refs)) {
            $message = implode('<br><br>', $refs) . '<br><br>Are you still sure you\'d like to delete it?';
        } else {
            $message = 'Are you sure you\'d like to delete this attachment?';
        }

        return new HtmlString($message);
    }

    protected function collectRelatedReferenceNumbers($relations, $relatedAttachments, $allReferenceNumbers = [])
    {
        $attachments = collect($relatedAttachments);

        foreach ($relations as $relation) {
            $mapping = $this->relationMappings[$relation];

            $numbers = $attachments
                ->pluck("{$relation}.reference_number")
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($numbers)) {
                $list = implode(', ', $numbers);
                $allReferenceNumbers[] = "This attachment is used in the following {$mapping['entity_name']}(s):<br>{$list}.";
            }
        }

        return $allReferenceNumbers;
    }

    public static function removeAttachment(Repeater $component, $index): void
    {
        $items = $component->getState();

        if (!isset($items[$index])) {
            return;
        }

        Attachment::find($items[$index]['id'])?->delete();

        unset($items[$index]);
        $component->state(array_values($items));
    }
}
