<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\PaymentRequest;
use App\Services\traits\BpCredentials;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class BotpressService
{
    use BpCredentials;

    public function __construct()
    {
        $this->initializeBpCredentials();
    }

    public function createTable(string $tableName, array $columns)
    {
        $createUrl = "{$this->botpressUrl}/tables";

        $properties = collect($columns)->mapWithKeys(function ($columnDefinition) {
            $columnName = $columnDefinition['name'];
            $columnType = $columnDefinition['type'] ?? 'string';

            return [
                $columnName => [
                    'type' => $columnType,
                    'x-zui' => [],
                    'nullable' => true,
                ]
            ];
        })->toArray();

        $createPayload = [
            'name' => $tableName,
            'schema' => [
                'type' => 'object',
                'x-zui' => [],
                'properties' => $properties,
                'additionalProperties' => true
            ],
        ];

        return $this->sendRequest($createUrl, $createPayload, "creating table {$tableName}");
    }

    public function insertAllTableRows(string $tableName, string $modelClass, array $columnMap)
    {
        $models = match ($modelClass) {
            Grade::class => $modelClass::where('id', '!=', 0)->get(),
            PaymentRequest::class => $modelClass::with('payments')->where('department_id', 6)->get(),
            default => $modelClass::all(),
        };

        if ($models->isEmpty()) {
            return response()->json([
                'error' => 'No data found in ' . class_basename($modelClass) . ' model to upload.'
            ], 404);
        }

        $rowDataArray = $this->prepareRowData($models, $columnMap);

        $insertUrl = "{$this->botpressUrl}/tables/{$tableName}/rows/upsert";
        $insertPayload = [
            'keyColumn' => 'id',
            'rows' => $rowDataArray,
        ];


        return $this->sendRequest($insertUrl, $insertPayload, "inserting rows for {$tableName}");
    }

    public function fetchAllTableRows(string $tableName)
    {
        $fetchUrl = "{$this->botpressUrl}/tables/{$tableName}/rows/find";
        $fetchPayload = [
            "query" => [
                "limit" => 100,
                "offset" => 0,
                "orderBy" => "createdAt",
                "orderDirection" => "asc",
                "filter" => new \stdClass,
            ]
        ];

        try {
            $response = $this->getHttpClient()->post($fetchUrl, $fetchPayload);
            if ($response->failed()) {
                return response()->json([
                    'error' => "Error fetching records for {$tableName}",
                    'details' => $response->json()
                ], $response->status());
            }
            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'error' => "An error occurred while fetching records for {$tableName}.",
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteAllTableRows(string $tableName)
    {
        $listResponse = $this->fetchAllTableRows($tableName);

        if (isset($listResponse['error'])) {
            return response()->json([
                'error' => "Error fetching row IDs for deletion from {$tableName}",
                'details' => $listResponse['details'] ?? $listResponse['error']
            ], 500);
        }

        $rows = $listResponse['rows'] ?? [];
        $rowIdsToDelete = array_column($rows, 'id');

        if (empty($rowIdsToDelete)) {
            return response()->json([
                'message' => "No rows found in {$tableName} to delete."
            ], 200);
        }

        $deleteUrl = "{$this->botpressUrl}/tables/{$tableName}/rows/delete";
        $deletePayload = [
            "ids" => $rowIdsToDelete
        ];


        return $this->sendRequest($deleteUrl, $deletePayload, "deleting rows from {$tableName}");
    }


    // Computations
    private function prepareRowData($models, array $columnMap): array
    {
        return $models->map(function ($model) use ($columnMap) {
            return collect($columnMap)->mapWithKeys(function ($modelAttribute, $botpressColumn) use ($model) {
                return [$botpressColumn => $this->getModelValue($model, $modelAttribute, $botpressColumn)];
            })->toArray();
        })->toArray();
    }

    private function getModelValue(Model $model, $modelAttribute, string $botpressColumn)
    {
        $value = match (true) {
            is_string($modelAttribute)  => $model->{$modelAttribute},
            is_callable($modelAttribute) => $modelAttribute($model),
            default                     => null,
        };

        $numericColumns = [
            'provisional_quantity', 'final_quantity', 'provisional_price', 'final_price',
            'remaining', 'payment', 'initial_payment', 'provisional_payment',
            'total', 'initial_total', 'provisional_total', 'final_total'
        ];

        return match (true) {
            in_array($botpressColumn, ['proforma_date', 'order_date', 'date']) => $value ? $value->format('Y-m-d') : null,
            in_array($botpressColumn, $numericColumns) && is_numeric($value)  => (float)$value,
            default => $value,
        };
    }

    private function sendRequest(string $url, array $payload, string $action)
    {
        try {
            $response = $this->getHttpClient()->post($url, $payload);
            if ($response->failed()) {
                return response()->json([
                    'error' => "Error {$action}.",
                    'details' => $response->json()
                ], $response->status());
            }
            return response()->json([
                'message' => ucfirst($action) . " completed successfully!",
                'botpressResponse' => $response->json()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => "An error occurred during {$action}.",
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function getHttpClient()
    {
        return Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'x-bot-id' => $this->botId,
                'Content-Type' => 'application/json',
            ]);
    }
}
