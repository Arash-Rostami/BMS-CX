<?php

namespace App\Livewire\Components;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class UserGuideTopbar extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function userGuideAction(): Action
    {
        return Action::make('userGuide')
            ->icon('heroicon-o-book-open')
            ->label('User Guide')
            ->iconButton()
            ->color('gray')
            ->slideOver()
            ->modalHeading('User Guide')
            ->modalDescription('Workflow instructions and infomaps')
            ->modalContent(view('livewire.components.user-guide-slideover'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }

    public function openInfographicAction(): Action
    {
        return Action::make('openInfographic')
            ->modalHeading('Infographic')
            ->modalContent(fn (array $arguments) => new HtmlString('<div class="flex items-center justify-center p-4 bg-gray-100 dark:bg-gray-900 rounded-lg"><div class="text-center text-gray-500"><x-heroicon-o-photo class="w-16 h-16 mx-auto mb-4" />Placeholder Infographic for: <strong>' . ($arguments['info'] ?? 'Unknown') . '</strong></div></div>'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('4xl');
    }

    public function openVideoAction(): Action
    {
        return Action::make('openVideo')
            ->modalHeading('Video Walkthrough')
            ->modalContent(fn (array $arguments) => new HtmlString('<div class="aspect-video bg-black rounded-lg flex items-center justify-center"><div class="text-white text-center"><x-heroicon-o-play-circle class="w-16 h-16 mx-auto mb-4" />Placeholder Video Player for: <strong>' . ($arguments['video'] ?? 'Unknown') . '</strong></div></div>'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth('4xl');
    }

    public function openInfographic($infoId)
    {
        $this->mountAction('openInfographic', ['info' => $infoId]);
    }

    public function openVideo($videoId)
    {
        $this->mountAction('openVideo', ['video' => $videoId]);
    }

    public function render(): View
    {
        return view('livewire.components.user-guide-topbar');
    }
}
