<?php

namespace App\Http\Controllers;

use App\Services\Bot\OrderBotTable;
use App\Services\Bot\PaymentRequestBotTable;
use App\Services\Bot\ProformaInvoiceBotTable;
use App\Services\Bot\RelatedPaymentDataBotTable;
use App\Services\Bot\SupplierBotTable;
use App\Services\Bot\BuyerBotTable;
use App\Services\Bot\ProductBotTable;
use App\Services\Bot\CategoryBotTable;
use App\Services\Bot\GradeBotTable;
use App\Services\Bot\UserBotTable;
use App\Services\Bot\OrderDetailBotTable;
use App\Services\Bot\LogisticBotTable;
use App\Services\Bot\DocBotTable;
use App\Services\Bot\DeliveryTermBotTable;
use App\Services\Bot\PackagingBotTable;
use App\Services\Bot\PortOfDeliveryBotTable;
use App\Services\Bot\ShippingLineBotTable;

class BotController extends Controller
{
    /**
     * Group all table updater services into an associative array for generic operations.
     *
     * @var array
     */
    protected array $tableUpdaters = [];

    /**
     * Constructor injection for all updater services.
     */
    public function __construct(
        ProformaInvoiceBotTable    $proformaInvoiceTableUpdater,
        OrderBotTable              $orderTableUpdater,
        SupplierBotTable           $supplierTableUpdater,
        BuyerBotTable              $buyerTableUpdater,
        ProductBotTable            $productTableUpdater,
        CategoryBotTable           $categoryTableUpdater,
        GradeBotTable              $gradeTableUpdater,
        UserBotTable               $userTableUpdater,
        OrderDetailBotTable        $orderDetailTableUpdater,
        LogisticBotTable           $logisticTableUpdater,
        DocBotTable                $docTableUpdater,
        DeliveryTermBotTable       $deliveryTermTableUpdater,
        PackagingBotTable          $packagingTableUpdater,
        PortOfDeliveryBotTable     $portOfDeliveryTableUpdater,
        ShippingLineBotTable       $shippingLineTableUpdater,
        PaymentRequestBotTable     $paymentRequestTableUpdater,
        RelatedPaymentDataBotTable $relatedPaymentDataTableUpdater
    )
    {
        $this->tableUpdaters = [
            'proformaInvoiceTable' => $proformaInvoiceTableUpdater,
            'orderTable' => $orderTableUpdater,
            'supplierTable' => $supplierTableUpdater,
            'buyerTable' => $buyerTableUpdater,
            'productTable' => $productTableUpdater,
            'categoryTable' => $categoryTableUpdater,
            'gradeTable' => $gradeTableUpdater,
            'userTable' => $userTableUpdater,
            'orderDetailTable' => $orderDetailTableUpdater,
            'logisticTable' => $logisticTableUpdater,
            'docTable' => $docTableUpdater,
            'deliveryTermTable' => $deliveryTermTableUpdater,
            'packagingTable' => $packagingTableUpdater,
            'portOfDeliveryTable' => $portOfDeliveryTableUpdater,
            'shippingLineTable' => $shippingLineTableUpdater,
            'paymentRequestTable' => $paymentRequestTableUpdater,
            'relatedPaymentDataTable' => $relatedPaymentDataTableUpdater
        ];
    }

    /*
     * Individual endpoints for each table.
     */

    public function createTable(string $tableKey)
    {
        if (!isset($this->tableUpdaters[$tableKey])) {
            return response()->json(['error' => 'Invalid table key'], 400);
        }

        $result['table'] = $this->tableUpdaters[$tableKey]->create();

        return $this->getJsonResponse($result);
    }

    public function insertTable(string $tableKey)
    {
        if (!isset($this->tableUpdaters[$tableKey])) {
            return response()->json(['error' => 'Invalid table key'], 400);
        }

        $result = $this->tableUpdaters[$tableKey]->insert();

        return $this->getJsonResponse($result);
    }

    public function fetchTable(string $tableKey)
    {
        if (!isset($this->tableUpdaters[$tableKey])) {
            return response()->json(['error' => 'Invalid table key'], 400);
        }

        $result = $this->tableUpdaters[$tableKey]->fetch();

        return $this->getJsonResponse($result);
    }

    public function deleteTable(string $tableKey)
    {
        if (!isset($this->tableUpdaters[$tableKey])) {
            return response()->json(['error' => 'Invalid table key'], 400);
        }

        $result = $this->tableUpdaters[$tableKey]->delete();

        return $this->getJsonResponse($result);
    }

    /**
     * Bulk methods for all tables.
     */

    public function createTables($delay = 0)
    {
        $results = $this->processTables(function ($updater) {
           return $updater->create();
        }, $delay);

        return $this->getJsonResponse($results);
    }

    public function insertTables($delay = 0)
    {
        $results = $this->processTables(function ($updater) {
            return $updater->insert();
        }, $delay);

        return $this->getJsonResponse($results);
    }

    public function deleteTables($delay = 0)
    {
        $results = $this->processTables(function ($updater) {
            return $updater->delete();
        }, $delay);

        return $this->getJsonResponse($results, true);
    }

    /**
     * Private methods related
     */
    private function processTables(callable $operation, int $delay)
    {
        $results = [];
        $index = 0;

        foreach ($this->tableUpdaters as $tableName => $updater) {
            if ($index > 0 && $delay > 0) {
                sleep($delay);
            }
            $result = $operation($updater);
            $results[$tableName] = json_decode($result->getContent(), true);
            $index++;
        }

        return $results;
    }

    private function getJsonResponse($results): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => ' process completed.',
            'results' => $results,
        ], 200);
    }
}
