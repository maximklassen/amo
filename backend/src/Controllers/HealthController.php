<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Http\Request;
use App\Http\Response;

/** Zustandsprüfung für Monitoring und für den ersten Test, ob alles verkabelt ist. */
final class HealthController
{
    // Connection wird vom Container automatisch übergeben (Autowiring)
    public function __construct(private readonly Connection $db)
    {
    }

    public function show(Request $request): Response
    {
        $databaseOk = $this->db->ping();

        if (!$databaseOk) {
            return Response::failure(503, 'Datenbank nicht erreichbar');
        }

        return Response::success([
            'status'   => 'ok',
            'database' => 'ok',
            'time'     => date(DATE_ATOM),
        ]);
    }
}
