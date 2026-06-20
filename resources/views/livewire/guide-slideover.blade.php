@php
    $workflows = [
        ['id' => 'w1', 'title' => 'Dashboard',        'icon' => 'heroicon-o-home',                    'pdf' => '/guides/dashboard.pdf',       'video' => '/guides/media/dashboard.mp4',       'info' => '/guides/media/dashboard.png',       'mindmap' => '/guides/media/dashboard_map.png',       'quiz' => 'https://notebooklm.google.com/notebook/91c1c316-f5e2-4813-b37b-f7719f6d116a/artifact/e831f074-d595-4dfe-9064-bbfa86303d0b?utm_source=nlm_web_share&utm_medium=google_oo&utm_campaign=art_share_1&utm_content=&utm_smc=nlm_web_share_google_oo_art_share_1_'],
        ['id' => 'w2', 'title' => 'Case Summary',     'icon' => 'heroicon-o-magnifying-glass',        'pdf' => '/guides/caseSummary.pdf',     'video' => '/guides/media/caseSummary.mp4',     'info' => '/guides/media/caseSummary.png',     'mindmap' => '/guides/media/caseSummary_map.png',     'quiz' => 'https://notebooklm.google.com/notebook/bdd32db3-2ccd-48b3-bc06-6403ff2e7340/artifact/34e5dec2-39af-4dd9-a203-53a04d2ae57e?utm_source=nlm_web_share&utm_medium=google_oo&utm_campaign=art_share_1&utm_content=&utm_smc=nlm_web_share_google_oo_art_share_1_'],
        ['id' => 'w3', 'title' => 'Proforma Invoice', 'icon' => 'heroicon-o-clipboard-document-list', 'pdf' => '/guides/proformaInvoice.pdf', 'video' => '/guides/media/proformaInvoice.mp4', 'info' => '/guides/media/proformaInvoice.png', 'mindmap' => '/guides/media/proformaInvoice_map.png', 'quiz' => 'https://notebooklm.google.com/notebook/9dd5384e-5c88-4d64-9332-4491e15afdf9/artifact/f196b601-f0ff-41f2-8509-c1ea910c3a94?utm_source=nlm_web_share&utm_medium=google_oo&utm_campaign=art_share_1&utm_content=&utm_smc=nlm_web_share_google_oo_art_share_1_'],
        ['id' => 'w4', 'title' => 'Payment Requests', 'icon' => 'heroicon-o-credit-card',             'pdf' => '/guides/paymentRequest.pdf',  'video' => '/guides/media/paymentRequest.mp4',  'info' => '/guides/media/paymentRequest.png',  'mindmap' => '/guides/media/paymentRequest_map.png',  'quiz' => 'https://notebooklm.google.com/notebook/99bf2f4e-a3e1-42d2-a60a-caadcf58f936/artifact/8c796b7f-932f-4ba8-ab06-28ab07e43e6b?utm_source=nlm_web_share&utm_medium=google_oo&utm_campaign=art_share_1&utm_content=&utm_smc=nlm_web_share_google_oo_art_share_1_'],
        ['id' => 'w5', 'title' => 'Master Data',      'icon' => 'heroicon-o-circle-stack',            'pdf' => '/guides/masterData.pdf',      'video' => '/guides/media/masterData.mp4',      'info' => '/guides/media/masterData.png',      'mindmap' => '/guides/media/masterData_map.png',      'quiz' => 'https://notebooklm.google.com/notebook/1e5a1c4c-7f62-4068-a885-7aa7aa81e7e3/artifact/0c07bfa2-2fde-4e39-9fe6-378ecc30f213?utm_source=nlm_web_share&utm_medium=google_oo&utm_campaign=art_share_1&utm_content=&utm_smc=nlm_web_share_google_oo_art_share_1_'],
    ];

    $buttons = [
        'pdf'     => ['color' => 'gray',    'icon' => 'document-text',   'label' => 'Read PDF',    'type' => 'link'],
        'video'   => ['color' => 'rose',    'icon' => 'play-circle',     'label' => 'Watch Video', 'type' => 'video'],
        'info'    => ['color' => 'sky',     'icon' => 'photo',           'label' => 'Infographic', 'type' => 'image'],
        'mindmap' => ['color' => 'emerald', 'icon' => 'rectangle-group', 'label' => 'Mindmap',     'type' => 'image'],
    ];
@endphp

<div x-data="{ expanded: null }" class="flex flex-col h-full space-y-4">
    <div class="space-y-3">
        @foreach($workflows as $w)
            <div
                class="border border-gray-200 dark:border-white/10 rounded-lg overflow-hidden bg-white dark:bg-gray-900 shadow-sm transition-all duration-200"
                :class="expanded === '{{ $w['id'] }}' ? 'ring-1 ring-primary-500 border-primary-500' : ''">

                <button type="button"
                        @click="expanded = expanded === '{{ $w['id'] }}' ? null : '{{ $w['id'] }}'"
                        class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10 transition-colors text-left"
                        :class="expanded === '{{ $w['id'] }}' ? 'bg-primary-50/50 dark:bg-primary-500/10' : ''">

                    <div class="flex items-center gap-3">
                        <x-dynamic-component :component="$w['icon']"
                                             class="w-5 h-5 transition-colors duration-200 text-gray-400 dark:text-gray-500"
                                             x-bind:class="expanded === '{{ $w['id'] }}' ? 'text-primary-500 dark:text-primary-400' : ''"/>
                        <span class="text-sm font-semibold transition-colors duration-200 text-gray-900 dark:text-white"
                              :class="expanded === '{{ $w['id'] }}' ? 'text-primary-600 dark:text-primary-400' : ''">{{ $w['title'] }}</span>
                    </div>

                    <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                                x-bind:class="expanded === '{{ $w['id'] }}' ? 'rotate-90 text-primary-500' : ''"/>
                </button>

                <div x-show="expanded === '{{ $w['id'] }}'" x-collapse
                     class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-white/10 space-y-4">

                    <div class="flex flex-row items-stretch gap-2">
                        @foreach($buttons as $key => $btn)
                            @if(!empty($w[$key]))

                                @if($btn['type'] === 'link')
                                    <a href="{{ $w[$key] }}" target="_blank" rel="noopener noreferrer"
                                       class="flex-1 flex flex-col items-center gap-2 p-2 sm:p-3 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group border border-transparent hover:border-gray-200 dark:hover:border-white/10 text-center">
                                        <div
                                            class="w-10 h-10 rounded-md bg-{{ $btn['color'] }}-100 dark:bg-{{ $btn['color'] }}-900/30 flex items-center justify-center text-{{ $btn['color'] }}-600 dark:text-{{ $btn['color'] }}-400 transition-transform group-hover:scale-105 shrink-0">
                                            <x-dynamic-component :component="'heroicon-o-'.$btn['icon']"
                                                                 class="w-6 h-6"/>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">{{ $btn['label'] }}</p>
                                    </a>

                                @else
                                    <button type="button"
                                            @click="$dispatch('open-modal', { id: 'media-modal', type: '{{ $btn['type'] }}', src: '{{ $w[$key] }}', title: '{{ $btn['label'] }}' })"
                                            class="flex-1 flex flex-col items-center gap-2 p-2 sm:p-3 rounded-md hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group border border-transparent hover:border-gray-200 dark:hover:border-white/10 text-center">
                                        <div
                                            class="w-10 h-10 rounded-md bg-{{ $btn['color'] }}-100 dark:bg-{{ $btn['color'] }}-900/30 flex items-center justify-center text-{{ $btn['color'] }}-600 dark:text-{{ $btn['color'] }}-400 transition-transform group-hover:scale-105 shrink-0">
                                            <x-dynamic-component :component="'heroicon-o-'.$btn['icon']"
                                                                 class="w-6 h-6"/>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">{{ $btn['label'] }}</p>
                                    </button>
                                @endif

                            @endif
                        @endforeach
                    </div>

                    @if(!empty($w['quiz']))
                        <div
                            class="p-4 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-wheat">
                            <div>
                                <h4 class="text-sm font-semibold workflow-quiz-title">Ready to test your knowledge?</h4>
                                <p class="text-xs mt-1 workflow-quiz-desc">Take a quick assessment to solidify your
                                    understanding of this workflow.</p>
                            </div>
                            <a href="{{ $w['quiz'] }}" target="_blank" rel="noopener noreferrer"
                               class="shrink-0 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md transition-colors text-sm font-medium border border-transparent workflow-quiz-btn">
                                <x-heroicon-o-clipboard-document-check class="w-5 h-5"/>
                                Begin Quiz
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

<template x-teleport="body">
    <x-filament::modal
        id="media-modal"
        width="6xl"
        alignment="center"
        x-data="{ src: '', type: '', fs: false, title: '' }"
        @open-modal.window="if ($event.detail.id === 'media-modal') { src = $event.detail.src; type = $event.detail.type; title = $event.detail.title ?? '' }"
        @close-modal.window="if ($event.detail.id === 'media-modal') { src = ''; fs = false }"
        @keydown.escape.window="fs = false">

        <x-slot name="heading">
            <h2 class="text-lg font-semibold mb-4" x-text="title"></h2>
        </x-slot>

        <template x-if="src && type === 'video'">
            <div
                class="aspect-video bg-primary-500 rounded-lg overflow-hidden border border-gray-800 flex items-center justify-center">
                <video :src="src" controls autoplay @dblclick="fs = !fs"
                       :class="fs ? 'fixed inset-0 z-[9999] w-screen h-screen object-contain bg-black cursor-zoom-out' : 'w-full h-full object-contain cursor-zoom-in'"></video>
            </div>
        </template>

        <template x-if="src && type !== 'video'">
            <div
                class="flex items-center justify-center bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-200 dark:border-white/10"
                title="Double click to enlarge">
                <img :src="src" :alt="title" @dblclick="fs = !fs"
                     :class="fs ? 'fixed inset-0 z-[9999] w-screen h-screen object-contain bg-black cursor-zoom-out' : 'w-full max-h-[75vh] object-contain cursor-zoom-in'"/>
            </div>
        </template>

        <template x-if="!src">
            <div class="p-12 text-center text-gray-400 dark:text-gray-500 flex flex-col items-center">
                <x-heroicon-o-photo class="w-12 h-12 mb-3 opacity-50"/>
                <span class="text-sm font-medium">Media not available</span>
            </div>
        </template>
    </x-filament::modal>
</template>
