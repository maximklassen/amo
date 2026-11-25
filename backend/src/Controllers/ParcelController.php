<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Http\Request;
use App\Http\Response;

/** Zustandsprüfung für Monitoring und für den ersten Test, ob alles verkabelt ist. */
final class ParcelController
{
    // Connection wird vom Container automatisch übergeben (Autowiring)
    public function __construct(private readonly Connection $db) {}

    public function all(Request $request, bool $full = false): Response
    {
        $query = 'SELECT `id`, `number`, `size_m2`';
        if ($full) $query .= ', `has_water`, `has_power`, `has_hut`, `svg_path`, `status`';
        $query .= ' FROM parcels';
        $parcels = $this->db->select($query);

        return Response::json([
            'statuscode' => $parcels ? 200 : 503,
            'error' => !(bool)$parcels,
            'message' => $parcels ? 'OK' : 'Error',
            'response' => $parcels,
        ]);
    }

    public function show()
    {
        //
    }
}
