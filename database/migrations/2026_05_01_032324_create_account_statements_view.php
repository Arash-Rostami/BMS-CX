<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    // ── What changed ──────────────────────────────────────────────────────────
    //
    // counter_interest : NEW column — counter-interest consumed in this row
    //                    settlement → counter_interest_applied (offsets gross interest)
    //                    others     → 0
    //
    // accrued_interest : CHANGED for disbursements and settlements
    //                    disbursement → COALESCE(unpaid_interest, 0)
    //                      Negative value shows counter-interest banked from the credit
    //                      period (receipt sitting idle before disbursement consumed it).
    //                    settlement   → COALESCE(accrued_interest_in_period, 0) — gross, not net.
    //                      Gross interest accrued; use counter_interest column to see the offset.
    //
    // debit            : CHANGED for settlements
    //                    Was: net interest (accrued − counter_interest_applied)
    //                    Now: gross accrued_interest_in_period
    //
    // running_balance  : CHANGED formula → SUM(debit − credit − counter_interest)
    //                    Keeps the cumulative balance correct now that settlement
    //                    debit carries gross interest instead of net.
    //
    // net_movement     : CHANGED → credit − debit + counter_interest
    //                    = actual change in balance for that row (positive = balance fell)
    // ─────────────────────────────────────────────────────────────────────────

    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");

        // LOCAL: correlated subquery (MySQL 5.7 compatible)
        DB::statement("
            CREATE VIEW account_statements AS
            SELECT
                rs.*,
                rs.credit - rs.debit + rs.counter_interest AS net_movement,
                (
                    SELECT SUM(rs2.debit - rs2.credit - rs2.counter_interest)
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
                            CASE WHEN type = 'disbursement' THEN COALESCE(pre_credit_interest, 0) ELSE 0 END AS accrued_interest,
                            0                   AS counter_interest
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
                            COALESCE(s2.accrued_interest_in_period, 0),
                            s2.settlement_amount,
                            0,
                            COALESCE(s2.accrued_interest_in_period, 0),
                            COALESCE(s2.counter_interest_applied, 0)
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
                    CASE WHEN type = 'disbursement' THEN COALESCE(pre_credit_interest, 0) ELSE 0 END AS accrued_interest,
                    0                   AS counter_interest
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
                    COALESCE(s.accrued_interest_in_period, 0),
                    s.settlement_amount,
                    0,
                    COALESCE(s.accrued_interest_in_period, 0),
                    COALESCE(s.counter_interest_applied, 0)
                FROM settlements s
                JOIN ledger_entries l ON s.ledger_entry_id = l.id
            ) AS rs
        ");

        // PRODUCTION / MySQL 8+ — CTE + window function version (swap in locally)
        DB::statement("
            CREATE VIEW account_statements AS
            WITH raw AS (
                SELECT
                    CONCAT('LE-', id) AS statement_id,
                    id                AS ledger_entry_id,
                    account_id, transaction_date, type AS event_type, description,
                    CASE type WHEN 'disbursement' THEN 1 WHEN 'settlement' THEN 2 ELSE 3 END AS sort_order,
                    id AS original_id,
                    CASE WHEN type = 'disbursement' THEN total_disbursed_base ELSE 0 END AS debit,
                    CASE WHEN type = 'receipt'      THEN total_disbursed_base ELSE 0 END AS credit,
                    CASE WHEN type = 'disbursement' THEN COALESCE(applied_credit_amount, 0) ELSE 0 END AS applied_credit,
                    CASE WHEN type = 'disbursement' THEN COALESCE(pre_credit_interest, 0) ELSE 0 END AS accrued_interest,
                    0 AS counter_interest
                FROM ledger_entries
                UNION ALL
                SELECT
                    CONCAT('ST-', s.id), s.ledger_entry_id, l.account_id, s.transaction_date,
                    'settlement', COALESCE(l.description, CONCAT('Payment against ledger #', l.id)),
                    2, s.id,
                    COALESCE(s.accrued_interest_in_period, 0),
                    s.settlement_amount, 0,
                    COALESCE(s.accrued_interest_in_period, 0),
                    COALESCE(s.counter_interest_applied, 0)
                FROM settlements s
                JOIN ledger_entries l ON s.ledger_entry_id = l.id
            )
            SELECT
                raw.*,
                raw.credit - raw.debit + raw.counter_interest AS net_movement,
                SUM(raw.debit - raw.credit - raw.counter_interest) OVER (
                    PARTITION BY raw.account_id
                    ORDER BY raw.transaction_date ASC, raw.sort_order ASC, raw.original_id ASC
                ) AS running_balance
            FROM raw
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS account_statements");
    }
};
