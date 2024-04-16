<?php

namespace App\Filament\Resources\Operational\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Models\Packaging;
use Filament\Actions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Tabs;


class ViewQuote extends ViewRecord
{
    public static string $resource = QuoteResource::class;

    public function infolist(Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('✨Main')
                            ->schema([
                                Section::make()
                                    ->columns([
                                        'sm' => 2,
                                        'md' => 2,
                                        'xl' => 3,
                                        '2xl' => 3,
                                    ])->schema([
                                        $this->viewQuoteRequestCommodity(),
                                        $this->viewCommodityType(),
                                        $this->viewOriginPort(),
                                        $this->viewDestintionPort(),
                                        $this->viewTrasportationMeans(),
                                        $this->viewTransportationType(),
                                        $this->viewPackaging(),
                                        $this->viewPaymentTerms(),
                                        $this->viewOfferedRate(),
                                        $this->viewStichBLFee(),
                                        $this->viewFreeTime(),
                                        $this->viewTimeStamp(),
                                        $this->viewValidity(),
                                    ]),
                            ]),
                        Tabs\Tab::make('🔗 Extra | Attachment')
                            ->schema([
                                Section::make('Details')
                                    ->schema([
                                        $this->viewExtra(),
                                    ]),
                                Section::make('Attachments')
                                    ->description('Click to view all files attached in this Order ↓')
                                    ->schema([
                                        $this->viewAttachment()
                                    ])->collapsible()
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    /**
     * @return TextEntry
     */
    public function viewCommodityType(): TextEntry
    {
        return TextEntry::make('commodity_type')
            ->label('Commodity Type')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewOriginPort(): TextEntry
    {
        return TextEntry::make('origin_port')
            ->label('Origin Port | POL')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewDestintionPort(): TextEntry
    {
        return TextEntry::make('destination_port')
            ->label('Destination Port | POD')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewTrasportationMeans(): TextEntry
    {
        return TextEntry::make('transportation_means')
            ->label('Transportation Means')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewTransportationType(): TextEntry
    {
        return TextEntry::make('transportation_type')
            ->label('Transportation Type')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPackaging(): TextEntry
    {
        return TextEntry::make('packing_type')
            ->formatStateUsing(fn(string $state) => Packaging::find($state)->name)
            ->label('Packing')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewPaymentTerms(): TextEntry
    {
        return TextEntry::make('payment_terms')
            ->formatStateUsing(function (string $state) {
                return [
                    'cod' => 'Cash on Delivery (COD)',
                    'prepayment' => 'Prepayment',
                    'net_x_days' => 'Net X days',
                    'eom' => 'End of Month (EOM)',
                    'specific_date' => 'Specific Date',
                    'letter_of_credit' => 'Letter of Credit (LC)',
                ][$state];
            })
            ->label('Payment Terms')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewOfferedRate(): TextEntry
    {
        return TextEntry::make('offered_rate')
            ->label('Offered Rate')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewStichBLFee(): TextEntry
    {
        return TextEntry::make('switch_bl_fee')
            ->label('Switch BL Fee')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewFreeTime(): TextEntry
    {
        return TextEntry::make('free_time_pol')
            ->label('Free Time')
            ->color('warning')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewTimeStamp(): TextEntry
    {
        return TextEntry::make('created_at')
            ->label('Submitted Time')
            ->color('success')
            ->badge()
            ->since();
    }

    /**
     * @return TextEntry
     */
    public function viewValidity(): TextEntry
    {
        return TextEntry::make('validity')
            ->color('danger')
            ->badge();
    }

    /**
     * @return TextEntry
     */
    public function viewExtra(): TextEntry
    {
        return TextEntry::make('extra')
            ->label('')
            ->color('secondary');
    }

    /**
     * @return ImageEntry
     */
    public function viewAttachment(): ImageEntry
    {
        return ImageEntry::make('attachment.file_path')
            ->label('')
            ->extraAttributes(fn($state) => $state ? [
                'class' => 'cursor-pointer',
                'title' => '👁️‍',
                'onclick' => "showImage('" . url($state) . "')",
            ] : [])
            ->disk('quote')
            ->alignCenter()
            ->visibility('public');
    }

    /**
     * @return TextEntry
     */
    public function viewQuoteRequestCommodity(): TextEntry
    {
        return TextEntry::make('quoteRequest.commodity')
            ->label('Request For')
            ->words(6)
            ->tooltip(fn($state) => $state)
            ->badge();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
