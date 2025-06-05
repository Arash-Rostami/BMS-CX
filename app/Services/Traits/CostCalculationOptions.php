<?php

namespace App\Services\Traits;

trait CostCalculationOptions
{
    public array $statusOptions = [
        'Accepted',
        'Cancelled',
        'Closed',
        'Expired',
        'Rejected',
        'Sold',
        'Withdrawn',
        'Other'
    ];
    public array $transportTypeOptions = [
        'Initial/Pre-Carriage' => [
            'Road - Contractor to Port',
            'Road - Other Pre-carriage',
            'Road - Packing Material Transport',
            'Road - Producer to Contractor',
            'Inland Waterway - Pre-carriage',
            'Rail - Pre-carriage',
        ],
        'Main Carriage' => [
            'Air Charter',
            'Air Freight - Express/Courier',
            'Air Freight - Standard',
            'International Rail Freight',
            'Ocean Freight - Breakbulk',
            'Ocean Freight - Bulk',
            'Ocean Freight - Container (FCL)',
            'Ocean Freight - Container (LCL)',
            'Ocean Freight - Ro-Ro',
            'Ocean Freight (General)',
        ],
        'On-Carriage' => [
            'Inland Waterway - On-carriage',
            'Rail - On-carriage',
            'Road - On-carriage',
        ],
        'Other' => [
            'Other Types',
        ],
    ];
    public array $containerTypeOptions = [
        'Standard Dry Vans' => [
            '10ft Dry Van',
            '20ft Dry Van (20GP)',
            '40ft Dry Van (40GP)',
            '40ft High Cube (40HC)',
            '45ft High Cube',
        ],
        'Refrigerated' => [
            '20ft Reefer',
            '40ft High Cube Reefer',
        ],
        'Specialized Opens & Racks' => [
            '20ft Open Top',
            '40ft Open Top',
            '20ft Flat Rack',
            '40ft Flat Rack',
            '20ft Platform',
            '40ft Platform',
        ],
        'Temperature-controlled / Insulated' => [
            'Insulated / Thermal Container',
            'Tunnel Container',
            'Side Open (Side-Loading) Container',
        ],
        'Bulk & Liquids' => [
            'Tank Container',
            'Drum (Barrel) Container',
        ],
        'Other Specialty' => [
            'Cargo Storage Roll Container',
            'Car Carrier Container',
            'Half-Height Container',
            'Swap Body (Non-ISO)',
            'Special Purpose Container',
            'Other Specialized Container',
        ],
    ];
    public array $incotermsOptions = [
        'CFR (Cost and Freight)',
        'CIF (Cost, Insurance and Freight)',
        'CIP (Carriage and Insurance Paid To)',
        'CPT (Carriage Paid To)',
        'DAT (Delivered At Terminal)',
        'DDP (Delivered Duty Paid)',
        'DPU (Delivered at Place Unloaded)',
        'EXW (Ex Works)',
        'FCA (Free Carrier)',
        'FAS (Free Alongside Ship)',
        'FOB (Free On Board)',
    ];
}
