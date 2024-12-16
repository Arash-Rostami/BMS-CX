<?php

namespace App\Services\traits;

use App\Models\Packaging;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\PortMaker;
use Illuminate\Support\Facades\Cache;

trait QuoteData
{

    public mixed $originPort;
    public mixed $destinationPort;
    public string $commodity = '';
    public string $quantity = '';
    public string $volume = '';
    public mixed $packing;

    public string $freeTime = '';
    public string $freeTimePOD = '';
    public string $imco = '';
    public string $offeredRate = '';
    public string $switchBL = '';

    public string $localCharges = '';

    public mixed $validity = null;

    public mixed $extra = null;

    public mixed $attachment = null;
    public array $iranianPorts;
    public array $chinesePorts;
    public mixed $packagingOptions;
    public mixed $productOptions;
    public mixed $containerTypeOptions;
    public mixed $quoteProvider;
    public mixed $providerName;
    public mixed $quoteRequest;
    public mixed $quoteRequestDetails;

    public mixed $token;
    public mixed $attachmentId;

    public string $containerNumber = '';
    public string $containerType = '';


    public function initializeDate()
    {
        $this->loadPortData();
        $this->loadOptions();
        $this->initializeSessionData();
        $this->loadQuoteRequestDetails();
        $this->initializeQuoteDetails();
    }

    protected function loadPortData()
    {
        $this->iranianPorts = PortMaker::getIranianPorts();
        $this->chinesePorts = PortMaker::getChinesePorts();
    }

    protected function loadOptions()
    {
        $this->packagingOptions = Cache::remember('packaging_options', 60, fn() => Packaging::pluck('name', 'id'));
        $this->productOptions = Cache::remember('product_options', 60, fn() => Product::pluck('name', 'id'));
        $this->containerTypeOptions = $this->getContainerTypes();
    }

    protected function initializeSessionData()
    {
        $quoteToken = session('quoteToken');

        $this->providerName =
            ($quoteToken->quoteProvider->title ?? '') . ' ' . ($quoteToken->quoteProvider->name ?? '');
        $this->quoteProvider = data_get($quoteToken, 'quote_provider_id');
        $this->quoteRequest = data_get($quoteToken, 'quote_request_id');
        $this->token = data_get($quoteToken, 'token');
    }

    protected function loadQuoteRequestDetails()
    {
        $this->quoteRequestDetails = QuoteRequest::find($this->quoteRequest);

        if (!$this->quoteRequestDetails) {
            abort(404, 'Quote request details not found.');
        }
    }

    protected function initializeQuoteDetails()
    {
        $details = $this->quoteRequestDetails;

        $this->originPort = $details->origin_port;
        $this->destinationPort = $details->destination_port;
        $this->packing = $details->packing;
        $this->commodity = $details->commodity;
        $this->quantity = $details->gross_weight ?? '';
        $this->volume = $details->quantity ?? '';
        $this->containerType = $details->container_type ?? '';
        $this->attachmentId = '';
    }


    protected function getContainerTypes(): array
    {
        return [
            '20-foot Standard' => '20-foot Standard',
            '40-foot Standard' => '40-foot Standard',
            '40-foot High Cube' => '40-foot High Cube',
            '45-foot High Cube' => '45-foot High Cube',
            'Refrigerated (Reefer)' => 'Refrigerated (Reefer)',
            'Open Top' => 'Open Top',
            'Flat Rack' => 'Flat Rack',
            'ISO Tank' => 'ISO Tank',
            'Ventilated' => 'Ventilated',
            'Insulated/Thermal' => 'Insulated/Thermal',
        ];
    }


    public function rules()
    {
        return [
            'originPort' => 'required|string',
            'destinationPort' => 'required|string',
            'commodity' => 'nullable|string|max:255',
            'packing' => 'required|integer',
            'freeTime' => 'nullable|integer',
            'freeTimePOD' => 'nullable|integer',
            'imco' => 'nullable|string|max:255',
            'offeredRate' => 'nullable|string|max:255',
            'localCharges' => 'nullable|string|max:255',
            'switchBL' => 'nullable|string|max:255',
            'validity' => 'required|date',
            'extra' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'containerNumber' => 'nullable|string|max:50',
            'containerType' => 'required|string|max:50',
        ];
    }
}
