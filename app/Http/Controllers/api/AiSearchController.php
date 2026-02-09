<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\ProformaInvoice;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiSearchController extends Controller
{
    public function context(Request $request): JsonResponse
    {
        $q = trim($request->input('query'));
        $type = $request->input('entity_type');

        if (!$q) return response()->json(['message' => 'Query required'], 400);

        if ($type === 'proforma') if ($res = $this->findProforma($q)) return $this->success('proforma_context', $res);
        if ($type === 'order') if ($res = $this->findOrder($q)) return $this->success('order_context', $res);
        if ($type === 'payment_request') if ($res = $this->findPaymentRequest($q)) return $this->success('payment_request_context', $res);
        if ($type === 'payment') if ($res = $this->findPayment($q)) return $this->success('payment_transaction_context', $res);
        if ($type === 'supplier') if ($res = $this->findSupplier($q)) return $this->success('supplier_context', $res);
        if ($type === 'buyer') if ($res = $this->findBuyer($q)) return $this->success('buyer_context', $res);


        if ($res = $this->findProforma($q)) return $this->success('proforma_context', $res);
        if ($res = $this->findOrder($q)) return $this->success('order_context', $res);
        if ($res = $this->findPaymentRequest($q)) return $this->success('payment_request_context', $res);
        if ($res = $this->findPayment($q)) return $this->success('payment_transaction_context', $res);
        if ($res = $this->findSupplier($q)) return $this->success('supplier_context', $res);
        if ($res = $this->findBuyer($q)) return $this->success('buyer_context', $res);

        return response()->json(['message' => 'No relevant records found in database.'], 404);
    }

    private function findBuyer(string $q)
    {
        $buyer = Buyer::where('name', 'LIKE', "%{$q}%")->first();
        if (!$buyer) return null;

        $activeContracts = ProformaInvoice::where('buyer_id', $buyer->id)
            ->with(['activeOrders'])
            ->latest()
            ->take(5)
            ->get();

        return ['entity' => $buyer, 'recent_contracts' => $activeContracts];
    }

    private function findOrder(string $q)
    {
        return Order::with([
            'proformaInvoice.supplier',
            'proformaInvoice.buyer',
            'purchaseStatus', 'doc', 'orderDetail',
            'product', 'grade', 'category', 'attachments',
            'logistic.shippingLine',
            'logistic.portOfDelivery',
            'logistic.deliveryTerm',
            'party.buyer',
            'party.supplier',
            'party.packaging',
            'paymentRequests.payments'
        ])
            ->where('reference_number', 'LIKE', "%{$q}%")
            ->orWhere('order_number', 'LIKE', "%{$q}%")
            ->orWhere('invoice_number', 'LIKE', "%{$q}%")
            ->first();
    }

    private function findPayment(string $q)
    {
        return Payment::with([
            'paymentRequests.order.proformaInvoice',
            'paymentRequests.associatedProformaInvoices',
            'attachments'
        ])
            ->where('reference_number', 'LIKE', "%{$q}%")
            ->orWhere('transaction_id', 'LIKE', "%{$q}%")
            ->first();
    }

    private function findPaymentRequest(string $q)
    {
        return PaymentRequest::with([
            'payments',
            'beneficiary',
            'supplier',
            'contractor',
            'department',
            'costCenter',
            'associatedProformaInvoices',
            'order.proformaInvoice',
            'order.logistic'
        ])
            ->where('reference_number', 'LIKE', "%{$q}%")
            ->first();
    }

    private function findProforma(string $q)
    {
        return ProformaInvoice::with([
            'buyer', 'supplier', 'user', 'assignee', 'category', 'product', 'grade', 'attachments',
            'activeOrders.purchaseStatus',
            'activeOrders.logistic.shippingLine',
            'activeOrders.logistic.portOfDelivery',
            'activeOrders.doc',
            'activeOrders.paymentRequests.payments',
            'associatedPaymentRequests.payments',
            'associatedPaymentRequests.beneficiary'
        ])
            ->where('proforma_number', 'LIKE', "%{$q}%")
            ->orWhere('contract_number', 'LIKE', "%{$q}%")
            ->orWhere('reference_number', 'LIKE', "%{$q}%")
            ->first();
    }

    private function findSupplier(string $q)
    {
        $supplier = Supplier::where('name', 'LIKE', "%{$q}%")->first();
        if (!$supplier) return null;

        $activeContracts = ProformaInvoice::where('supplier_id', $supplier->id)
            ->with(['activeOrders', 'associatedPaymentRequests'])
            ->latest()
            ->take(5)
            ->get();

        return ['entity' => $supplier, 'recent_contracts' => $activeContracts];
    }

    private function success(string $type, mixed $data): JsonResponse
    {
        return response()->json([
            'type' => $type,
            'data' => $data instanceof Model ? $data : $data['entity'],
            'recent_contracts' => $data['recent_contracts'] ?? null
        ]);
    }
}
