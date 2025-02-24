<?php

namespace Filament\Widgets;

class FilamentInfoWidget extends Widget
{

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';


    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::widgets.filament-info-widget';
}
