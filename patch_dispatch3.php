<?php

$files = [
    'app/Filament/Resources/Financial/LedgerEntryResource/Pages/Admin.php',
    'app/Filament/Resources/Financial/SettlementResource/Pages/Admin.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    // Put back the $recalcDate parameter since we restored $fromDate in the constructor
    $content = str_replace(
        'RecalculateAccountLedger::dispatchSync($accountId);',
        'RecalculateAccountLedger::dispatchSync($accountId, $recalcDate);',
        $content
    );
    $content = str_replace(
        'RecalculateAccountLedger::dispatchSync($record->account_id);',
        'RecalculateAccountLedger::dispatchSync($record->account_id, $recalcDate);',
        $content
    );
    file_put_contents($file, $content);
}
