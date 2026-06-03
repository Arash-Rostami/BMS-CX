<?php
$content = file_get_contents('app/Models/AccountStatement.php');
$content = str_replace(
    "'net_movement' => 'decimal:6',",
    "'net_movement' => 'decimal:6',\n        'net' => 'decimal:6',",
    $content
);
file_put_contents('app/Models/AccountStatement.php', $content);
