<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
            CREATE OR REPLACE VIEW `view_contract` AS
            SELECT
                `pi`.`id` AS `pi_id`,
                `pi`.`reference_number` AS `pi_ref`,
                `pi`.`contract_number` AS `pi_contract`,
                `pi`.`proforma_date` AS `pi_date`,
                `pi`.`quantity` AS `pi_quantity`,
                `pi`.`category_id` AS `category_id`,
                `pi`.`status` AS `pi_status`,
                `pi`.`deleted_at` AS `pi_deleted_at`,
                `o`.`id` AS `order_id`,
                `o`.`part` AS `order_part`,
                `o`.`purchase_status_id` AS `purchase_status_id`,
                `o`.`order_status` AS `order_status`,
                `o`.`deleted_at` AS `order_deleted_at`,
                `d`.`id` AS `doc_id`,
                `d`.`BL_date` AS `bl_date`,
                `od`.`id` AS `order_detail_id`,
                COALESCE(`od`.`final_quantity`, `od`.`provisional_quantity`, `od`.`buying_quantity`, 0) AS `order_detail_quantity`
            FROM `proforma_invoices` `pi`
            LEFT JOIN `orders` `o`
                ON `o`.`proforma_invoice_id` = `pi`.`id`
            LEFT JOIN `docs` `d`
                ON `d`.`id` = `o`.`doc_id`
            LEFT JOIN `order_details` `od`
                ON `od`.`id` = `o`.`order_detail_id`
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS `view_contract`');
    }
};
