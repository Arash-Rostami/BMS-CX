<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS loan_summaries');

        DB::statement("
            CREATE VIEW loan_summaries AS
            SELECT
                le.id,
                le.type                  AS entry_type,
                le.description,
                le.transaction_date      AS disbursement_date,
                le.total_disbursed_base,
                sa.settlement_dates,
                sa.settlement_amounts,
                sa.duration_days,
                sa.accrued_interests,
                sa.deducted_from_principals,
                sa.counter_interests,
                le.applied_credit_amount,
                sa.avg_duration_days,
                sa.avg_settlement_amount,
                le.is_settled,
                le.remaining_principal,
                le.account_id
            FROM ledger_entries le
            LEFT JOIN (
                SELECT
                    ledger_entry_id,
                    GROUP_CONCAT(transaction_date            ORDER BY transaction_date SEPARATOR '\n') AS settlement_dates,
                    GROUP_CONCAT(settlement_amount           ORDER BY transaction_date SEPARATOR '\n') AS settlement_amounts,
                    GROUP_CONCAT(duration_days               ORDER BY transaction_date SEPARATOR '\n') AS duration_days,
                    GROUP_CONCAT(accrued_interest_in_period  ORDER BY transaction_date SEPARATOR '\n') AS accrued_interests,
                    GROUP_CONCAT(deducted_from_principal     ORDER BY transaction_date SEPARATOR '\n') AS deducted_from_principals,
                    GROUP_CONCAT(counter_interest_applied    ORDER BY transaction_date SEPARATOR '\n') AS counter_interests,
                    ROUND(AVG(duration_days), 0)             AS avg_duration_days,
                    ROUND(AVG(settlement_amount), 2)         AS avg_settlement_amount
                FROM settlements
                GROUP BY ledger_entry_id
            ) sa ON sa.ledger_entry_id = le.id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS loan_summaries');
    }
};
