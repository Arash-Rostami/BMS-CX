<?php

namespace App\Services;


class DeliveryDocumentService
{
    /**
     * Matrix of delivery‑term => [ document_key => allowed (bool) ]
     *
     */
    protected static array $matrix = [
        'EXW' => [
            'balance-payment-receipt' => true,
            'calculation-sheet' => true,
            'coa' => true,
            'containers-pickup-date' => true,
            'coo' => false,
            'dated-bl' => true,
            'declaration' => true,
            'demurrage' => true,
            'draft-bl' => false,
            'final-invoice' => true,
            'final-loading-list' => true,
            'insurance' => true,
            'license' => true,
            'license-final-invoices' => true,
            'packing-list' => true,
            'pci' => false,
            'pl' => true,
            'sgs' => true,
            'shipping-order' => false,
            'specification' => false,
            'split-costs' => false,
            'tds' => false,
            'telex-release' => true,
        ],
        'FCA' => [
            'balance-payment-receipt' => true,
            'calculation-sheet' => true,
            'coa' => true,
            'containers-pickup-date' => true,
            'coo' => false,
            'dated-bl' => true,
            'declaration' => true,
            'demurrage' => true,
            'draft-bl' => false,
            'final-invoice' => true,
            'final-loading-list' => false,
            'insurance' => true,
            'license' => true,
            'license-final-invoices' => true,
            'packing-list' => true,
            'pci' => false,
            'pl' => true,
            'sgs' => true,
            'shipping-order' => false,
            'specification' => false,
            'split-costs' => false,
            'tds' => false,
            'telex-release' => true,
        ],
        'FOB' => [
            'balance-payment-receipt' => true,
            'calculation-sheet' => true,
            'coa' => true,
            'containers-pickup-date' => true,
            'coo' => false,
            'dated-bl' => true,
            'declaration' => true,
            'demurrage' => true,
            'draft-bl' => false,
            'final-invoice' => true,
            'final-loading-list' => false,
            'insurance' => true,
            'license' => true,
            'license-final-invoices' => true,
            'packing-list' => true,
            'pci' => false,
            'pl' => true,
            'sgs' => true,
            'shipping-order' => false,
            'specification' => false,
            'split-costs' => false,
            'tds' => false,
            'telex-release' => true,
        ],
        'CFR' => [
            'balance-payment-receipt' => true,
            'calculation-sheet' => true,
            'coa' => true,
            'containers-pickup-date' => false,
            'coo' => true,
            'dated-bl' => true,
            'declaration' => true,
            'demurrage' => false,
            'draft-bl' => false,
            'final-invoice' => true,
            'final-loading-list' => false,
            'insurance' => false,
            'license' => true,
            'license-final-invoices' => true,
            'packing-list' => false,
            'pci' => true,
            'pl' => true,
            'sgs' => false,
            'shipping-order' => false,
            'specification' => false,
            'split-costs' => true,
            'tds' => false,
            'telex-release' => true,
        ],
    ];

    public static function getForTerm(string $term): array
    {
        return static::$matrix[$term] ?? [];
    }
}
