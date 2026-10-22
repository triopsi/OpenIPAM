<?php

namespace App\Support;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

/**
 * Keep a container-generated encryption key stable across restarts and updates.
 */
final class PersistentApplicationKey
{
    /**
     * Read the stored key, creating it under an exclusive lock on first startup.
     */
    public static function getOrCreate(string $path): string
    {
        $previousMask = umask(0077);

        try {
            $file = fopen($path, 'c+');
        } finally {
            umask($previousMask);
        }

        if ($file === false) {
            throw new RuntimeException('Cannot open the application key file. Check storage permissions.');
        }

        try {
            if (! flock($file, LOCK_EX)) {
                throw new RuntimeException('Cannot lock the application key file.');
            }

            $key = trim(stream_get_contents($file));

            if ($key === '') {
                $key = 'base64:'.base64_encode(Encrypter::generateKey('AES-256-CBC'));

                if (fwrite($file, $key) !== strlen($key) || ! fflush($file)) {
                    throw new RuntimeException('Cannot save the application key.');
                }
            }

            $decodedKey = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : false;

            if ($decodedKey === false || ! Encrypter::supported($decodedKey, 'AES-256-CBC')) {
                throw new RuntimeException('The stored application key is invalid. Restore it from your backup.');
            }

            return $key;
        } finally {
            fclose($file);
        }
    }
}
