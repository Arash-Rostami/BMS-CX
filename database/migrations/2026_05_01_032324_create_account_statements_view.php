<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");
        DB::statement("
            CREATE VIEW account_statements AS
            WITH raw_statements AS (
                SELECT
                    id AS original_id,
                    account_id,
                    transaction_date,
                    type AS event_type,
                    description,
                    total_disbursed_base AS debit,
                    0 AS credit,
                    0 AS accrued_interest
                FROM ledger_entries

                UNION ALL

                SELECT
                    s.id AS original_id,
                    l.account_id,
                    s.transaction_date,
                    'settlement' AS event_type,
                    'Payment against ledger #' || l.id AS description,
                    0 AS debit,
                    s.settlement_amount AS credit,
                    s.accrued_interest_in_period AS accrued_interest
                FROM settlements s
                JOIN ledger_entries l ON s.ledger_entry_id = l.id
            )
            SELECT
                rs.*,
                SUM(rs.debit - rs.credit + rs.accrued_interest) OVER (
                    PARTITION BY rs.account_id
                    ORDER BY rs.transaction_date ASC, rs.event_type ASC, rs.original_id ASC
                ) AS running_balance
            FROM raw_statements rs
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");
    }
};
