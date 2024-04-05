<?php

namespace App\Livewire;

use App\Models\Attachment;
use App\Models\Quote;
use App\Models\QuoteToken;
use App\Services\traits\QuoteData;
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

        $attachmentId = $this->attachment ? $this->handleFileUpload($this->attachment) : null;

        $quote = $this->createQuote($attachmentId);

        if ($quote) {
            $this->nullifyToken($quote);
        }

        return back()->with('message', 'Quote submitted successfully!');
    }

    private function handleFileUpload($fileObj)
    {
        $attachment = new Attachment();
        $attachment->file_path = $this->storeAttachment($this->attachment);
        $attachment->save();

        return $attachment->id;
    }


    private function storeAttachment($fileObj)
    {
        $fileName = uniqid() . '.' . $fileObj->getClientOriginalExtension();
        return $fileObj->storeAs('quote-attachments', $fileName, 'quote');
    }

    private function createQuote($attachmentId)
    {
        return Quote::create([
            'transportation_means' => $this->transportationMeans,
            'transportation_type' => $this->transportationType,
            'origin_port' => $this->originPort,
            'destination_port' => $this->destinationPort,
            'offered_rate' => $this->offeredRate,
            'switch_bl_fee' => $this->switchBL,
            'commodity_type' => $this->commodity,
            'packing_type' => $this->packing,
            'payment_terms' => $this->paymentTerm,
            'free_time_pol' => $this->freeTime,
            'free_time_pod' => $this->freeTime,
            'validity' => $this->validity,
            'extra' => $this->extra,
            'quote_request_id' => $this->quoteRequest,
            'quote_provider_id' => $this->quoteProvider,
            'attachment_id' => $attachment->id ?? null,
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
