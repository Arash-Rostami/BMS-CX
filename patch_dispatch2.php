<?php

$file = 'app/Filament/Resources/Financial/LedgerEntryResource/Pages/Admin.php';
$content = file_get_contents($file);
$content = str_replace(
    'RecalculateAccountLedger::dispatchSync($accountId);',
    'RecalculateAccountLedger::dispatchSync($record->account_id);',
    $content
);
file_put_contents($file, $content);

// Let's check SettlementResource too just in case it had a similar bug
$file2 = 'app/Filament/Resources/Financial/SettlementResource/Pages/Admin.php';
$content2 = file_get_contents($file2);
$content2 = str_replace(
    'RecalculateAccountLedger::dispatchSync($record->account_id);',
    'RecalculateAccountLedger::dispatchSync($accountId);', // In Settlement it was originally $accountId
    $content2
);
file_put_contents($file2, $content2);
