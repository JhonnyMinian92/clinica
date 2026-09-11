<?php

/**
 * Diagnostico temporal de conectividad MySQL para InfinityFree.
 *
 * IMPORTANTE:
 * - No muestra DB_PASS ni ningun secreto.
 * - Debe eliminarse despues de confirmar el diagnostico.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $credentials = clinica_db_credentials();

    echo "=== DIAGNOSTICO MYSQL CLINICA ===\n\n";
    echo "APP_ENV: " . $credentials['environment'] . "\n";
    echo "DB_HOST: " . $credentials['host'] . "\n";
    echo "DB_PORT: " . $credentials['port'] . "\n";
    echo "DB_NAME: " . $credentials['dbname'] . "\n";
    echo "DB_USER: " . $credentials['user'] . "\n";
    echo "DB_PASS: [OCULTO]\n\n";

    $host = (string) $credentials['host'];
    $port = (int) $credentials['port'];

    echo "--- PRUEBA TCP ---\n";
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);

    if ($socket === false) {
        echo "TCP: FALLA\n";
        echo "TCP errno: " . $errno . "\n";
        echo "TCP error: " . ($errstr !== '' ? $errstr : 'sin detalle') . "\n\n";
    } else {
        fclose($socket);
        echo "TCP: OK - el servidor PHP puede alcanzar el puerto MySQL.\n\n";
    }

    echo "--- PRUEBA PDO ---\n";
    try {
        $pdo = clinica_get_db();
        $pdo->query('SELECT 1');
        echo "PDO: OK - conexion y autenticacion MySQL correctas.\n";
    } catch (PDOException $e) {
        echo "PDO: FALLA\n";
        echo "PDO exception: " . get_class($e) . "\n";
        echo "PDO error: " . $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo "DIAGNOSTICO: FALLA\n";
    echo "Exception: " . get_class($e) . "\n";
    echo "Error: " . $e->getMessage() . "\n";
}
