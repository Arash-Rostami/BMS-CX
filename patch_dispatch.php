<?php

$files = [
    'app/Filament/Resources/Financial/LedgerEntryResource/Pages/Admin.php',
    'app/Filament/Resources/Financial/SettlementResource/Pages/Admin.php'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace('/RecalculateAccountLedger::dispatchSync\([^,]+,\s*\$recalcDate\);/', 'RecalculateAccountLedger::dispatchSync($accountId);', $content);
    $content = preg_replace('/RecalculateAccountLedger::dispatchSync\(\$record->account_id,\s*\$recalcDate\);/', 'RecalculateAccountLedger::dispatchSync($record->account_id);', $content);
    file_put_contents($file, $content);
}
