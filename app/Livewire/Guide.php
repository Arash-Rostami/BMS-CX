<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Guide extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function userGuideAction(): Action
    {
        return Action::make('userGuide')
            ->icon('heroicon-o-book-open')
            ->label('BMS Guide')
            ->tooltip('📖 Guide')
            ->iconButton()
            ->color('gray')
            ->slideOver()
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->modalHeading('📖 BMS Guide')
            ->modalDescription('Essential documentation, visuals, and workflow assessments.')
            ->modalContent(fn (): View => view('livewire.guide-slideover'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }
    public function render(): View
    {
        return view('components.guide');
    }
}
