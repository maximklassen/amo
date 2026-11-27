<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

final class InvoiceController
{
    public function __construct(private readonly Connection $db) {
    }

    public function getInvoices(Request $request): Response
    {
        return Response::success($this->fetchInvoices($request->attribute('user')['id']));
    }
    public function getInvoice(Request $request): Response
    {
        $id = (int)$request->param('id');
        $userId = $request->attribute('user')['id'];

        $invoice = $this->fetchInvoices($userId, ' AND i.`id` = :id', ['id' => $id])[0] ?? null;
        if (!$invoice) {
            throw HttpException::notFound();
        }
        return Response::success($invoice);
    }

    private function fetchInvoices(int $userId, string $where = '', array $params = []): array
    {
        $query = 'SELECT i.`id`, i.`number`, i.`year`, i.`issued_at`, i.`due_at`, i.`status`, p.`number` AS parcel_number,';
        $query .= ' it.`id` AS item_id, it.`description`, it.`quantity`, it.`unit`, it.`unit_price`, it.`amount`';
        $query .= ' FROM invoices i JOIN parcels p ON p.`id` = i.`parcel_id`';
        $query .= ' LEFT JOIN invoice_items it ON it.`invoice_id` = i.`id`';
        $query .= ' WHERE i.`user_id` = :user_id' . $where . ' ORDER BY i.`issued_at` DESC, it.`id`';
        $rows = $this->db->select($query, ['user_id' => $userId] + $params);

        $invoices = [];
        foreach ($rows as $row) {
            $invoices[$row['id']] ??= [
                'id' => $row['id'],
                'number' => $row['number'],
                'year' => $row['year'],
                'parcel_number' => $row['parcel_number'],
                'issued_at' => $row['issued_at'],
                'due_at' => $row['due_at'],
                'status' => $row['status'],
                'total' => 0.0,
                'items' => [],
            ];
            if ($row['item_id']) {
                $invoices[$row['id']]['items'][] = [
                    'id' => $row['item_id'],
                    'description' => $row['description'],
                    'quantity' => (float)$row['quantity'],
                    'unit' => $row['unit'],
                    'unit_price' => (float)$row['unit_price'],
                    'amount' => (float)$row['amount'],
                ];
                $invoices[$row['id']]['total'] += (float)$row['amount'];
            }
        }

        return array_values($invoices);
    }
}
