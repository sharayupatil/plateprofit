<?php

namespace App\Services\Imports;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PosImport;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use SplFileObject;
use Throwable;

class PlatePilotCsvImporter
{
    private const REQUIRED_HEADERS = [
        'order_id',
        'order_date',
        'customer_name',
        'customer_phone',
        'product_name',
        'category',
        'quantity',
        'unit_price',
        'total',
        'payment_method',
    ];

    public function import(PosImport $posImport): void
    {
        $absolutePath = Storage::disk('local')
            ->path($posImport->stored_path);

        if (! file_exists($absolutePath)) {
            throw new RuntimeException('The stored CSV file was not found.');
        }

        $file = new SplFileObject($absolutePath, 'r');

        $file->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::SKIP_EMPTY
            | SplFileObject::DROP_NEW_LINE
        );

        $headers = $this->readHeaders($file);
        $this->validateHeaders($headers);

        $orders = [];
        $rowNumber = 1;

        while (! $file->eof()) {
            $values = $file->fgetcsv();
            $rowNumber++;

            if ($this->isEmptyRow($values)) {
                continue;
            }

            $posImport->increment('rows_processed');

            if (count($values) !== count($headers)) {
                $this->recordError(
                    $posImport,
                    $rowNumber,
                    $values,
                    'The number of values does not match the CSV headers.'
                );

                continue;
            }

            $row = array_combine($headers, $values);
            $originalOrderDate = trim((string) $row['order_date']);
            $row = $this->normaliseRow($row);

            $validator = Validator::make(
                $row,
                $this->rowValidationRules()
            );

            if ($validator->fails()) {
                $row['order_date'] = $originalOrderDate;
                $this->recordError(
                    $posImport,
                    $rowNumber,
                    $row,
                    implode(' ', $validator->errors()->all())
                );

                continue;
            }

            $orders[$row['order_id']][] = [
                'row_number' => $rowNumber,
                'data' => $row,
                'original_order_date' => $originalOrderDate,
            ];
        }

        foreach ($orders as $externalOrderId => $orderRows) {
            try {
                DB::transaction(function () use (
                    $posImport,
                    $externalOrderId,
                    $orderRows
                ) {
                    $this->importOrder(
                        $posImport,
                        $externalOrderId,
                        $orderRows
                    );
                });

                $posImport->increment(
                    'rows_successful',
                    count($orderRows)
                );
            } catch (Throwable $exception) {
                foreach ($orderRows as $orderRow) {
                    $orderRow['data']['order_date'] = $orderRow['original_order_date'];
                    $this->recordError(
                        $posImport,
                        $orderRow['row_number'],
                        $orderRow['data'],
                        $exception->getMessage()
                    );
                }
            }
        }

        $posImport->refresh();

        $posImport->update([
            'status' => $posImport->rows_failed > 0
                ? 'completed_with_errors'
                : 'completed',
            'completed_at' => now(),
        ]);
    }

    private function readHeaders(SplFileObject $file): array
    {
        $headers = $file->fgetcsv();

        if (! is_array($headers) || $this->isEmptyRow($headers)) {
            throw new RuntimeException(
                'The CSV file does not contain a header row.'
            );
        }

        return array_map(function ($header) {
            $header = trim((string) $header);

            // Remove UTF-8 BOM from the first header.
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

            return strtolower($header);
        }, $headers);
    }

    private function validateHeaders(array $headers): void
    {
        $missingHeaders = array_diff(
            self::REQUIRED_HEADERS,
            $headers
        );

        if ($missingHeaders !== []) {
            throw new RuntimeException(
                'Missing required CSV columns: '
                .implode(', ', $missingHeaders)
            );
        }
    }

    private function rowValidationRules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:100'],
            'order_date' => ['required', 'date'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'product_name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_method' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    private function normaliseRow(array $row): array
    {
        return [
            'order_id' => trim((string) $row['order_id']),
            'order_date' => $this->normaliseOrderDate($row['order_date']),
            'customer_name' => $this->nullableString(
                $row['customer_name']
            ),
            'customer_phone' => $this->normalisePhone(
                $row['customer_phone']
            ),
            'product_name' => trim(
                (string) $row['product_name']
            ),
            'category' => $this->nullableString(
                $row['category']
            ),
            'quantity' => trim((string) $row['quantity']),
            'unit_price' => trim((string) $row['unit_price']),
            'total' => trim((string) $row['total']),
            'payment_method' => $this->nullableString(
                $row['payment_method']
            ),
        ];
    }

    private function normaliseOrderDate(mixed $value): string
    {
        $date = trim((string) $value);

        if (preg_match('~^(\d{1,2})/(\d{1,2})/(\d{4})(?=\s|$)~', $date, $matches)) {
            return sprintf('%s-%02d-%02d', $matches[3], $matches[2], $matches[1])
                .substr($date, strlen($matches[0]));
        }

        return $date;
    }

    private function importOrder(
        PosImport $posImport,
        string $externalOrderId,
        array $orderRows
    ): void {
        $firstRow = $orderRows[0]['data'];
        $orderedAt = Carbon::parse($firstRow['order_date']);

        $customer = $this->findOrCreateCustomer(
            $posImport,
            $firstRow,
            $orderedAt
        );

        $order = Order::updateOrCreate(
            [
                'restaurant_id' => $posImport->restaurant_id,
                'source' => 'platepilot_csv',
                'external_order_id' => $externalOrderId,
            ],
            [
                'customer_id' => $customer?->id,
                'pos_import_id' => $posImport->id,
                'ordered_at' => $orderedAt,
                'payment_method' => $firstRow['payment_method'],
            ]
        );

        foreach ($orderRows as $orderRow) {
            $row = $orderRow['data'];

            $product = Product::firstOrCreate(
                [
                    'restaurant_id' => $posImport->restaurant_id,
                    'name' => $row['product_name'],
                ],
                [
                    'category' => $row['category'],
                ]
            );

            $calculatedLineTotal = round(
                (int) $row['quantity']
                * (float) $row['unit_price'],
                2
            );

            $csvLineTotal = round((float) $row['total'], 2);

            if (abs($calculatedLineTotal - $csvLineTotal) > 0.01) {
                throw new RuntimeException(
                    "The line total for {$row['product_name']} "
                    .'does not equal quantity multiplied by unit price.'
                );
            }

            $order->items()->updateOrCreate(
                [
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => (int) $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total' => $csvLineTotal,
                ]
            );
        }

        $orderTotal = $order->items()->sum('total');

        $order->update([
            'subtotal' => $orderTotal,
            'total' => $orderTotal,
        ]);

        if ($customer) {
            $this->updateCustomerOrderDates(
                $customer,
                $orderedAt
            );
        }
    }

    private function findOrCreateCustomer(
        PosImport $posImport,
        array $row,
        Carbon $orderedAt
    ): ?Customer {
        if (
            empty($row['customer_phone'])
            && empty($row['customer_name'])
        ) {
            return null;
        }

        if (! empty($row['customer_phone'])) {
            return Customer::firstOrCreate(
                [
                    'restaurant_id' => $posImport->restaurant_id,
                    'phone' => $row['customer_phone'],
                ],
                [
                    'name' => $row['customer_name'],
                    'first_order_at' => $orderedAt,
                    'last_order_at' => $orderedAt,
                ]
            );
        }

        return Customer::firstOrCreate(
            [
                'restaurant_id' => $posImport->restaurant_id,
                'name' => $row['customer_name'],
                'phone' => null,
            ],
            [
                'first_order_at' => $orderedAt,
                'last_order_at' => $orderedAt,
            ]
        );
    }

    private function updateCustomerOrderDates(
        Customer $customer,
        Carbon $orderedAt
    ): void {
        $firstOrderAt = $customer->first_order_at;

        if (! $firstOrderAt || $orderedAt->lt($firstOrderAt)) {
            $customer->first_order_at = $orderedAt;
        }

        $lastOrderAt = $customer->last_order_at;

        if (! $lastOrderAt || $orderedAt->gt($lastOrderAt)) {
            $customer->last_order_at = $orderedAt;
        }

        $customer->save();
    }

    private function recordError(
        PosImport $posImport,
        int $rowNumber,
        array $rowData,
        string $message
    ): void {
        $posImport->errors()->create([
            'row_number' => $rowNumber,
            'row_data' => $rowData,
            'message' => $message,
        ]);

        $posImport->increment('rows_failed');
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalisePhone(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $hasLeadingPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        return $hasLeadingPlus ? '+'.$digits : $digits;
    }

    private function isEmptyRow(mixed $values): bool
    {
        if (! is_array($values)) {
            return true;
        }

        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
