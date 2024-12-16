<?php

namespace App\Services\Notification\SMS;

class ProformaInvoiceMessage
{

    public string $product;
    public string $company;
    public string $reference_number;
    public string $type;


    /**
     * Create a new notification instance.
     */
    public function __construct($record, $type = 'new')
    {
        $this->product = $record->product->name;
        $this->company = $record->buyer->name;
        $this->reference_number = $record->reference_number;
        $this->type = $type;
    }


    public function print()
    {
        return ($this->type == 'new')
            ? $this->showCreatedMessage()
            : ($this->type == 'edit' ? $this->showEditedMessage() : $this->showDeletedMessage());
    }


    public function showCreatedMessage()
    {
        return $this->formatMessage(
            "A new pro forma invoice has been created.",
            "Reference: {$this->reference_number}\nCompany: {$this->company}\nProduct: {$this->product}"
        );
    }

    public function showEditedMessage()
    {
        return $this->formatMessage(
            "The pro forma invoice has been edited.",
            "Reference: {$this->reference_number}\nCompany: {$this->company}\nProduct: {$this->product}"
        );
    }

    public function showDeletedMessage()
    {
        return $this->formatMessage(
            "The pro forma invoice has been deleted.",
            "Reference: {$this->reference_number}\nCompany: {$this->company}\nProduct: {$this->product}"
        );
    }

    private function formatMessage(string $title, string $body): string
    {
        return implode("\n\n", [
            "BMS Notification Service",
            $title,
            $body,
        ]);
    }
}
