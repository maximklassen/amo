<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Http\Request;
use App\Http\Response;

final class MetersController
{
    public function __construct(private readonly Connection $db) {
    }

    public function getMeters(Request $request): Response
    {
        return Response::success($this->fetchMeters($request->attribute('user')['id']));
    }

    public function addMeterReading(Request $request): Response
    {
        $id = (int)$request->param('id');
        $userId = $request->attribute('user')['id'];
        $value = $request->body()['value'] ?? null;

        if (!is_numeric($value) || $value < 0) {
            return Response::json(['statuscode' => 400, 'error' => true, 'message' => 'Ungültiger Wert', 'response' => null]);
        }

        $meter = $this->fetchMeters($userId, ' AND m.`id` = :id', ['id' => $id])[0] ?? null;
        if (!$meter) {
            return Response::json(['statuscode' => 404, 'error' => true, 'message' => 'Not found', 'response' => null]);
        }

        $today = date('Y-m-d');
        $prev = array_filter($meter['readings'], fn($r) => $r['read_at'] < $today);
        if ($prev && $value < end($prev)['value']) {
            return Response::json(['statuscode' => 400, 'error' => true, 'message' => 'Wert kleiner als der vorherige Stand', 'response' => null]);
        }
        
        // Pro Zähler ein Stand pro Tag: erneute Eingabe am selben Tag überschreibt
        $this->db->upsert('meter_readings', ['meter_id' => $id, 'read_at' => date('Y-m-d'), 'value' => $value], ['meter_id', 'read_at']);

        return Response::json([
            'statuscode' => 201,
            'error' => false,
            'message' => 'OK',
            'response' => $this->fetchMeters($userId, ' AND m.`id` = :id', ['id' => $id])[0],
        ]);
    }

    private function fetchMeters(int $userId, string $where = '', array $params = []): array
    {
        $query = 'SELECT m.`id`, m.`type`, m.`number`, p.`number` AS parcel_number,';
        $query .= ' r.`id` AS reading_id, r.`read_at`, r.`value`';
        $query .= ' FROM meters m JOIN parcels p ON p.`id` = m.`parcel_id`';
        // Nur Zähler von Parzellen, die der Nutzer aktuell pachtet
        $query .= ' JOIN leases l ON l.`parcel_id` = p.`id` AND l.`user_id` = :user_id';
        $query .= ' AND (l.`end_date` IS NULL OR l.`end_date` > CURDATE())';
        $query .= ' LEFT JOIN meter_readings r ON r.`meter_id` = m.`id`';
        $query .= ' WHERE m.`removed_at` IS NULL' . $where . ' ORDER BY m.`id`, r.`read_at`';
        $rows = $this->db->select($query, ['user_id' => $userId] + $params);

        $meters = [];
        foreach ($rows as $row) {
            $meters[$row['id']] ??= [
                'id' => $row['id'],
                'type' => $row['type'],
                'number' => $row['number'],
                'parcel_number' => $row['parcel_number'],
                'readings' => [],
            ];
            if ($row['reading_id']) {
                $meters[$row['id']]['readings'][] = ['id' => $row['reading_id'], 'read_at' => $row['read_at'], 'value' => (float)$row['value']];
            }
        }

        return array_values($meters);
    }
}
