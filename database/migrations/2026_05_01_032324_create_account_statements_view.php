<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ── Column reference ──────────────────────────────────────────────────────
    //
    // statement_id    : globally unique row key — 'LE-{id}' or 'ST-{id}'
    // ledger_entry_id : always the originating LedgerEntry id (safe for BelongsTo)
    // event_type      : 'disbursement' | 'receipt' | 'settlement'
    // sort_order      : intra-day ordering — disbursement(1) → settlement(2) → receipt(3)
    // debit           : balance-increasing events
    //                   disbursement → total_disbursed_base (gross contract amount)
    //                   settlement   → net accrued interest (gross − counter_interest)
    //                   receipt      → 0
    // credit          : balance-reducing events
    //                   settlement   → cash paid by borrower
    //                   receipt      → overpayment / prior credit
    //                   disbursement → 0
    // applied_credit  : prior credit consumed at disbursement birth (disbursement rows only)
    //                   gross debit − applied_credit = true new net exposure
    // accrued_interest: net interest charged in this settlement period (= debit for settlements)
    // net_movement    : credit − debit  (positive = balance fell, negative = balance rose)
    // running_balance : SUM(debit − credit) cumulative per account, chronological
    //                   positive → borrower owes us  |  negative → borrower has credit
    // ─────────────────────────────────────────────────────────────────────────

    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");

        // LOCAL: correlated subquery (MySQL 5.7 compatible)
        DB::statement("
            CREATE VIEW account_statements AS
            SELECT
                rs.*,
                rs.credit - rs.debit AS net_movement,
                (
                    SELECT SUM(rs2.debit - rs2.credit)
                    FROM (
                        SELECT
                            CONCAT('LE-', id)   AS statement_id,
                            id                  AS ledger_entry_id,
                            account_id,
                            transaction_date,
                            type                AS event_type,
                            CASE type
                                WHEN 'disbursement' THEN 1
                                WHEN 'settlement'   THEN 2
                                ELSE 3
                            END                 AS sort_order,
                            id                  AS original_id,
                            CASE WHEN type = 'disbursement' THEN total_disbursed_base ELSE 0 END AS debit,
                            CASE WHEN type = 'receipt'      THEN total_disbursed_base ELSE 0 END AS credit,
                            CASE WHEN type = 'disbursement' THEN COALESCE(applied_credit_amount, 0) ELSE 0 END AS applied_credit,
                            0                   AS accrued_interest
                        FROM ledger_entries

                        UNION ALL

                        SELECT
                            CONCAT('ST-', s2.id),
                            s2.ledger_entry_id,
                            l2.account_id,
                            s2.transaction_date,
                            'settlement',
                            2,
                            s2.id,
                            s2.accrued_interest_in_period - COALESCE(s2.counter_interest_applied, 0),
                            s2.settlement_amount,
                            0,
                            s2.accrued_interest_in_period - COALESCE(s2.counter_interest_applied, 0)
                        FROM settlements s2
                        JOIN ledger_entries l2 ON s2.ledger_entry_id = l2.id
                    ) rs2
                    WHERE rs2.account_id = rs.account_id
                      AND (
                              rs2.transaction_date < rs.transaction_date
                          OR (rs2.transaction_date = rs.transaction_date AND rs2.sort_order < rs.sort_order)
                          OR (rs2.transaction_date = rs.transaction_date AND rs2.sort_order = rs.sort_order AND rs2.original_id <= rs.original_id)
                      )
                ) AS running_balance
            FROM (
                SELECT
                    CONCAT('LE-', id)   AS statement_id,
                    id                  AS ledger_entry_id,
                    account_id,
                    transaction_date,
                    type                AS event_type,
                    CASE type
                        WHEN 'disbursement' THEN 1
                        WHEN 'settlement'   THEN 2
                        ELSE 3
                    END                 AS sort_order,
                    id                  AS original_id,
                    description,
                    CASE WHEN type = 'disbursement' THEN total_disbursed_base ELSE 0 END AS debit,
                    CASE WHEN type = 'receipt'      THEN total_disbursed_base ELSE 0 END AS credit,
                    CASE WHEN type = 'disbursement' THEN COALESCE(applied_credit_amount, 0) ELSE 0 END AS applied_credit,
                    0                   AS accrued_interest
                FROM ledger_entries

                UNION ALL

                SELECT
                    CONCAT('ST-', s.id),
                    s.ledger_entry_id,
                    l.account_id,
                    s.transaction_date,
                    'settlement',
                    2,
                    s.id,
                    COALESCE(l.description, CONCAT('Payment against ledger #', l.id)),
                    s.accrued_interest_in_period - COALESCE(s.counter_interest_applied, 0),
                    s.settlement_amount,
                    0,
                    s.accrued_interest_in_period - COALESCE(s.counter_interest_applied, 0)
                FROM settlements s
                JOIN ledger_entries l ON s.ledger_entry_id = l.id
            ) AS rs
        ");

        // PRODUCTION / MySQL 8+ — CTE + window function version (swap in locally)
        //
        // DB::statement("
        //     CREATE VIEW account_statements AS
        //     WITH raw AS (
        //         SELECT
        //             CONCAT('LE-', id) AS statement_id,
        //             id                AS ledger_entry_id,
        //             account_id, transaction_date, type AS event_type, description,
        //             CASE type WHEN 'disbursement' THEN 1 WHEN 'settlement' THEN 2 ELSE 3 END AS sort_order,
        //             id AS original_id,
        //             CASE WHEN type = 'disbursement' THEN total_disbursed_base ELSE 0 END AS debit,
        //             CASE WHEN type = 'receipt'      THEN total_disbursed_base ELSE 0 END AS credit,
        //             CASE WHEN type = 'disbursement' THEN COALESCE(applied_credit_amount, 0) ELSE 0 END AS applied_credit,
        //             0 AS accrued_interest
        //         FROM ledger_entries
        //         UNION ALL
        //         SELECT
        //             CONCAT('ST-', s.id), s.ledger_entry_id, l.account_id, s.transaction_date,
        //             'settlement', COALESCE(l.description, CONCAT('Payment against ledger #', l.id)),
        //             2, s.id,
        //             s.accrued_interest_in_period - COALESCE(s.counter_interest_applied, 0),
        //             s.settlement_amount, 0,
        //             s.accrued_interest_in_period - COALESCE(s.counter_interest_applied, 0)
        //         FROM settlements s
        //         JOIN ledger_entries l ON s.ledger_entry_id = l.id
        //     )
        //     SELECT
        //         raw.*,
        //         raw.credit - raw.debit AS net_movement,
        //         SUM(raw.debit - raw.credit) OVER (
        //             PARTITION BY raw.account_id
        //             ORDER BY raw.transaction_date ASC, raw.sort_order ASC, raw.original_id ASC
        //         ) AS running_balance
        //     FROM raw
        // ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");
    }
};
