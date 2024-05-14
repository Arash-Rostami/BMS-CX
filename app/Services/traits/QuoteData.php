<?php

namespace App\Services\traits;

use App\Models\DeliveryTerm;
use App\Models\Packaging;
use App\Models\QuoteRequest;
use App\Services\PortMaker;

trait QuoteData
{

    public mixed $originPort;
    public mixed $destinationPort;
    public string $commodity = '';
    public mixed $packing;
    public mixed $transportationType = '';
    public string $transportationMeans = '';
    public string $freeTime = '';
    public string $imco = '';
    public string $offeredRate = '';
    public string $switchBL = '';
    public mixed $paymentTerm = null;

    public mixed $validity = null;

    public mixed $extra = null;

    public mixed $attachment = null;
    public array $iranianPorts;
    public array $chinesePorts;
    public mixed $packagingOptions;
    public mixed $deliveryTerms;
    public mixed $paymentTerms;
    public mixed $quoteProvider;
    public mixed $quoteRequest;
    public mixed $quoteRequestDetails;

    public mixed $token;
    public mixed $attachmentId;


    public function initializeDate()
    {
        $this->initializePaymentTerms();

        $this->iranianPorts = PortMaker::getIranianPorts();
        $this->chinesePorts = PortMaker::getChinesePorts();


        $this->packagingOptions = Cache::remember('packaging_options', 60, function () {
            return Packaging::pluck('name', 'id');
        });

        $this->deliveryTerms = Cache::remember('delivery_terms', 60, function () {
            return DeliveryTerm::pluck('name', 'id');
        });

        $this->quoteProvider = data_get(session('quoteToken'), 'quote_provider_id');
        $this->quoteRequest = data_get(session('quoteToken'), 'quote_request_id');
        $this->token =  data_get(session('quoteToken'), 'token');

        $this->quoteRequestDetails = QuoteRequest::find($this->quoteRequest);
        $this->originPort = $this->quoteRequestDetails->origin_port;
        $this->destinationPort = $this->quoteRequestDetails->destination_port;;
        $this->packing = $this->quoteRequestDetails->packing;

        $this->attachmentId = '';
    }

    protected function initializePaymentTerms()
    {
        $this->paymentTerms = [
            'cod' => 'Cash on Delivery (COD)',
            'prepayment' => 'Prepayment',
            'net_x_days' => 'Net X days',
            'eom' => 'End of Month (EOM)',
            'specific_date' => 'Specific Date',
            'letter_of_credit' => 'Letter of Credit (LC)',
        ];
    }

    public function rules()
    {
        return [
            'originPort' => 'required|string',
            'destinationPort' => 'required|string',
            'packing' => 'required|integer',
            'transportationMeans' => 'nullable|string|max:255',
            'transportationType' => 'nullable|string|max:255',
            'freeTime' => 'nullable|integer',
            'imco' => 'nullable|string|max:255',
            'offeredRate' => 'nullable|string|max:255',
            'switchBL' => 'nullable|string|max:255',
            'validity' => 'required|date',
            'commodity' => 'nullable|string|max:255',
            'extra' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3500',
        ];
    }
}
