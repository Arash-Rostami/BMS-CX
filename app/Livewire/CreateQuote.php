<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Quote;
use App\Models\QuoteToken;
use App\Services\Traits\QuoteData;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateQuote extends Component
{

    use WithFileUploads, QuoteData;


    public function mount()
    {
        $this->initializeDate();
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function submit()
    {
        $this->validate();

        $this->attachmentId = $this->attachment ? $this->handleFileUpload() : null;

        $quote = $this->createQuote($this->attachmentId);

        if ($quote) {
            $this->nullifyToken($quote);
        }

        return back()->with("message-{$this->attachmentId}", 'Quote submitted successfully!');
    }

    private function handleFileUpload()
    {
        $attachment = new Attachment();
        $attachment->file_path = $this->storeAttachment();
        $attachment->save();

        return $attachment->id;
    }


    private function storeAttachment()
    {
        $fileName = uniqid() . '.' . $this->attachment->getClientOriginalExtension();
        return $this->attachment->storeAs('attachments/quote', $fileName, 'quote');
    }

    private function createQuote($attachmentId)
    {
        return Quote::create([
            'origin_port' => $this->originPort,
            'destination_port' => $this->destinationPort,
            'offered_rate' => $this->offeredRate,
            'local_charges' => $this->localCharges,
            'switch_bl_fee' => $this->switchBL,
            'commodity_type' => $this->commodity,
            'packing_type' => $this->packing,
            'free_time_pol' => $this->freeTime,
            'free_time_pod' => $this->freeTimePOD,
            'validity' => $this->validity,
            'extra' => $this->extra,
            'quote_request_id' => $this->quoteRequest,
            'quote_provider_id' => $this->quoteProvider,
            'attachment_id' => $attachmentId ?? null,
            'container_number' => $this->containerNumber,
            'container_type' => $this->containerType,
        ]);
    }

    private function nullifyToken($quote)
    {
        QuoteToken::where('token', $this->token)
            ->where('quote_request_id', $this->quoteRequest)
            ->update(['quote_id' => $quote->id]);
    }

    public function render()
    {
        return view('livewire.create-quote');
    }
}
