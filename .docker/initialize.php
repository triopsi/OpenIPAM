<?php

use App\Support\PersistentApplicationKey;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

// An explicit key keeps existing installations compatible. Otherwise, persist
// the generated key in the same volume as the database and uploaded files.
echo getenv('APP_KEY') ?: PersistentApplicationKey::getOrCreate($root.'/storage/app.key');
