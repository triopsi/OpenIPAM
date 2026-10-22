<?php

namespace Tests\Unit\Support;

use App\Support\PersistentApplicationKey;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Verify that generated encryption keys survive restarts and concurrent starts.
 */
class PersistentApplicationKeyTest extends TestCase
{
    private string $directory;

    /**
     * Isolate key files from application storage and real credentials.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/openipam-key-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    /**
     * Remove only the temporary file created by each test.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->directory.'/app.key')) {
            unlink($this->directory.'/app.key');
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    /**
     * Create a valid AES-256 key readable only by its owner.
     *
     * Expect: A private mode-0600 file contains a base64-encoded 32-byte encryption key.
     */
    public function test_new_key_is_valid_and_private(): void
    {
        $path = $this->directory.'/app.key';
        $key = PersistentApplicationKey::getOrCreate($path);

        $this->assertStringStartsWith('base64:', $key);
        $this->assertSame(32, strlen(base64_decode(substr($key, 7), true)));
        $this->assertSame($key, file_get_contents($path));
        $this->assertSame(0600, fileperms($path) & 0777);
    }

    /**
     * Reuse the same key rather than rotating it on later starts.
     *
     * Expect: Repeated initialization returns the original stored key unchanged.
     */
    public function test_existing_key_is_preserved(): void
    {
        $path = $this->directory.'/app.key';
        $key = PersistentApplicationKey::getOrCreate($path);

        $this->assertSame($key, PersistentApplicationKey::getOrCreate($path));
    }

    /**
     * Refuse a damaged key file without replacing its contents.
     *
     * Expect: A damaged key raises an exception and its file contents remain unchanged.
     */
    public function test_invalid_key_fails_without_rotating_it(): void
    {
        $path = $this->directory.'/app.key';
        file_put_contents($path, 'damaged-key');

        try {
            PersistentApplicationKey::getOrCreate($path);
            $this->fail('A damaged stored key must fail startup.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('invalid', $exception->getMessage());
            $this->assertSame('damaged-key', file_get_contents($path));
        }
    }

    /**
     * Give an explicitly configured deployment key precedence at startup.
     *
     * Expect: The initializer returns the explicit deployment key rather than generating another one.
     */
    public function test_explicit_key_takes_precedence_in_initializer(): void
    {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $process = new Process([PHP_BINARY, dirname(__DIR__, 3).'/.docker/initialize.php'], env: ['APP_KEY' => $key]);
        $process->mustRun();

        $this->assertSame($key, $process->getOutput());
    }

    /**
     * Share one generated key across simultaneous container initializations.
     *
     * Expect: All four concurrent processes succeed and return the same persisted key.
     */
    public function test_concurrent_initializers_use_the_same_key(): void
    {
        $autoload = dirname(__DIR__, 3).'/vendor/autoload.php';
        $script = 'require '.var_export($autoload, true).'; echo \\App\\Support\\PersistentApplicationKey::getOrCreate('.var_export($this->directory.'/app.key', true).');';
        $processes = [];

        for ($i = 0; $i < 4; $i++) {
            $process = new Process([PHP_BINARY, '-r', $script]);
            $process->start();
            $processes[] = $process;
        }

        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $this->assertSame(file_get_contents($this->directory.'/app.key'), $process->getOutput());
        }
    }
}
