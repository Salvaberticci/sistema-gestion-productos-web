<?php
require_once __DIR__ . '/includes/auth.php';

$phpTime = date('Y-m-d H:i:s');
$db = getDB();
$dbTime = $db->query("SELECT NOW()")->fetchColumn();

echo "<h1>🕒 Verificación de Zona Horaria (Venezuela)</h1>";
echo "<p><b>PHP Time:</b> $phpTime (America/Caracas)</p>";
echo "<p><b>DB Time:</b> $dbTime (UTC-4)</p>";

if (substr($phpTime, 0, 16) === substr($dbTime, 0, 16)) {
    echo "<p style='color:green;'>✅ ¡Sincronización Exitosa! Ambos sistemas están alineados.</p>";
} else {
    echo "<p style='color:red;'>⚠️ Hay una discrepancia. Revisa la configuración.</p>";
}

echo "<hr>";
echo "<a href='index.php'>Volver al Inicio</a>";
