<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    // CREATE OR REPLACE VIEW account_statements AS
    // WITH raw_statements AS (
    //     SELECT
    //         id AS original_id,
    //         account_id,
    //         transaction_date,
    //         type AS event_type,
    //         description,
    //         CASE WHEN type = 'receipt' THEN 0 ELSE total_disbursed_base END AS debit,
    //         CASE WHEN type = 'receipt' THEN total_disbursed_base ELSE 0 END AS credit,
    //         0 AS accrued_interest
    //     FROM ledger_entries
    //
    //     UNION ALL
    //
    //     SELECT
    //         s.id,
    //         l.account_id,
    //         s.transaction_date,
    //         'settlement',
    //         COALESCE(l.description, CONCAT('Payment against ledger #', l.id)),
    //         0,
    //         s.settlement_amount,
    //         s.accrued_interest_in_period
    //     FROM settlements s
    //     JOIN ledger_entries l ON s.ledger_entry_id = l.id
    // )
    // SELECT
    //     rs.*,
    //     SUM(rs.debit - rs.credit + rs.accrued_interest) OVER (
    //         PARTITION BY rs.account_id
    //         ORDER BY rs.transaction_date ASC, rs.event_type ASC, rs.original_id ASC
    //     ) AS running_balance
    // FROM raw_statements rs;

    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");
        DB::statement("
            CREATE VIEW account_statements AS
            SELECT
                rs.*,
                (
                    SELECT SUM(rs2.debit - rs2.credit + rs2.accrued_interest)
                    FROM (
                        SELECT
                            id AS original_id,
                            account_id,
                            transaction_date,
                            type AS event_type,
                            CASE WHEN type = 'receipt' THEN 0 ELSE total_disbursed_base END AS debit,
                            CASE WHEN type = 'receipt' THEN total_disbursed_base ELSE 0 END AS credit,
                            0 AS accrued_interest
                        FROM ledger_entries

                        UNION ALL

                        SELECT
                            s2.id, l2.account_id, s2.transaction_date,
                            'settlement',
                            0, s2.settlement_amount, s2.accrued_interest_in_period
                        FROM settlements s2
                        JOIN ledger_entries l2 ON s2.ledger_entry_id = l2.id
                    ) rs2
                    WHERE rs2.account_id = rs.account_id
                      AND (
                          rs2.transaction_date < rs.transaction_date OR
                          (rs2.transaction_date = rs.transaction_date AND rs2.event_type < rs.event_type) OR
                          (rs2.transaction_date = rs.transaction_date AND rs2.event_type = rs.event_type AND rs2.original_id <= rs.original_id)
                      )
                ) AS running_balance
            FROM (
                SELECT
                    id AS original_id,
                    account_id,
                    transaction_date,
                    type AS event_type,
                    description,
                    CASE WHEN type = 'receipt' THEN 0 ELSE total_disbursed_base END AS debit,
                    CASE WHEN type = 'receipt' THEN total_disbursed_base ELSE 0 END AS credit,
                    0 AS accrued_interest
                FROM ledger_entries

                UNION ALL

                SELECT
                    s.id, l.account_id, s.transaction_date,
                    'settlement',
                    COALESCE(l.description, CONCAT('Payment against ledger #', l.id)),
                    0, s.settlement_amount, s.accrued_interest_in_period
                FROM settlements s
                JOIN ledger_entries l ON s.ledger_entry_id = l.id
            ) AS rs
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");
    }
};
