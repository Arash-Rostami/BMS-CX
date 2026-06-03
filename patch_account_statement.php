<?php
$content = file_get_contents('database/migrations/2026_05_01_032324_create_account_statements_view.php');
$content = str_replace(
    "rs.credit - rs.debit + rs.counter_interest AS net_movement,",
    "rs.debit - rs.credit - rs.counter_interest AS net_movement,",
    $content
);
$content = str_replace(
    "raw.credit - raw.debit + raw.counter_interest AS net_movement,",
    "raw.debit - raw.credit - raw.counter_interest AS net_movement,",
    $content
);
file_put_contents('database/migrations/2026_05_01_032324_create_account_statements_view.php', $content);
