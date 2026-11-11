<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=UTF-8');header('Cache-Control: no-store');
try{require __DIR__.'/../config/database.php';$version=database()->query("SELECT version FROM portal_migrations WHERE version='20261106_participant_portal'")->fetchColumn();if(!$version)throw new RuntimeException('Migration missing');echo 'ok';}catch(Throwable $e){http_response_code(503);echo 'unavailable';}
