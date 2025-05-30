<?php
/**
 * Simple PHP development server for the B13 Ollama Chat Interface
 * 
 * Usage: php server.php [port]
 * Default port: 8000
 */

$port = isset($argv[1]) ? (int)$argv[1] : 8000;
$host = '0.0.0.0';
$docroot = __DIR__;

echo "Starting B13 Ollama Chat Interface server...\n";
echo "Server: http://{$host}:{$port}\n";
echo "Document root: {$docroot}\n";
echo "Press Ctrl+C to stop the server\n\n";

// Start the built-in PHP server
$command = sprintf(
    'php -S %s:%d -t %s',
    $host,
    $port,
    escapeshellarg($docroot)
);

passthru($command);