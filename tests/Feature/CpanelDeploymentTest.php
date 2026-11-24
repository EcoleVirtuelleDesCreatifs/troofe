<?php

namespace Tests\Feature;

use App\Support\CpanelDeployment;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CpanelDeploymentTest extends TestCase
{
    private string $directory;
    private string $repository;
    private string $release;
    private string $public;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/troofe-cpanel-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0755, true);
        $this->directory = realpath($this->directory);
        $this->repository = $this->directory.'/repositories/troofe';
        $this->release = $this->directory.'/troofe-releases/release-first';
        $this->public = $this->directory.'/public_html';
        foreach ([$this->repository.'/storage/app/public', $this->public] as $path) {
            mkdir($path, 0755, true);
        }
        $this->makeRelease($this->release, 'first');
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function makeRelease(string $release, string $content): void
    {
        mkdir($release.'/public/assets', 0755, true);
        mkdir($release.'/deployment', 0755, true);
        file_put_contents($release.'/public/index.php', '<?php echo "relative-entry";');
        file_put_contents($release.'/public/.htaccess', 'RewriteEngine On');
        file_put_contents($release.'/public/assets/app.css', $content);
    }

    private function environment(): array
    {
        return ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => 'https://troofe.net', 'APP_KEY' => 'base64:'.base64_encode(str_repeat('k', 32)), 'SESSION_SECURE_COOKIE' => 'true', 'DB_CONNECTION' => 'mysql', 'DB_HOST' => 'localhost', 'DB_DATABASE' => 'account_test', 'DB_USERNAME' => 'account_app', 'DB_PASSWORD' => 'test-only', 'MAIL_MAILER' => 'smtp', 'MAIL_HOST' => 'smtp.example.test', 'MAIL_USERNAME' => 'test@example.test', 'MAIL_PASSWORD' => 'test-only', 'MAIL_FROM_ADDRESS' => 'test@example.test', 'QUEUE_CONNECTION' => 'database', 'PAYMENT_DRIVER' => 'simulation'];
    }

    public static function invalidConfiguration(): array
    {
        return [
            ['APP_ENV', 'local'], ['APP_DEBUG', 'true'], ['APP_URL', 'https://troofe.net/fr'],
            ['APP_KEY', 'invalid'], ['SESSION_SECURE_COOKIE', 'false'], ['DB_PASSWORD', ''],
            ['DB_USERNAME', 'root'], ['MAIL_MAILER', 'log'], ['MAIL_PASSWORD', ''],
            ['QUEUE_CONNECTION', 'sync'], ['PAYMENT_DRIVER', 'unknown'], ['PAYMENT_DRIVER', 'cinetpay'],
        ];
    }

    #[DataProvider('invalidConfiguration')]
    public function test_unsafe_configuration_is_rejected(string $key, string $value): void
    {
        $this->expectException(DomainException::class);
        CpanelDeployment::validateEnvironment(array_replace($this->environment(), [$key => $value]));
    }

    public function test_valid_configuration_can_be_checked_without_publishing(): void
    {
        CpanelDeployment::validateEnvironment($this->environment());
        $this->assertSame(['.', '..'], scandir($this->public));
    }

    public function test_only_public_files_and_a_correct_private_entry_point_are_published(): void
    {
        file_put_contents($this->release.'/public/.env', 'DO_NOT_PUBLISH');
        mkdir($this->release.'/public/.git');
        file_put_contents($this->release.'/public/.git/config', 'DO_NOT_PUBLISH');
        mkdir($this->public.'/.well-known');
        file_put_contents($this->public.'/.well-known/proof', 'keep');
        (new CpanelDeployment($this->repository, $this->release))->publish();
        $entry = file_get_contents($this->public.'/index.php');
        $this->assertStringContainsString(var_export($this->release, true), $entry);
        $this->assertStringContainsString('usePublicPath(__DIR__)', $entry);
        $this->assertStringNotContainsString('relative-entry', $entry);
        $this->assertStringNotContainsString('DO_NOT_PUBLISH', $entry);
        $this->assertFileDoesNotExist($this->public.'/.env');
        $this->assertDirectoryDoesNotExist($this->public.'/.git');
        $this->assertSame('first', file_get_contents($this->public.'/assets/app.css'));
        $this->assertSame('keep', file_get_contents($this->public.'/.well-known/proof'));
        $this->assertSame(realpath($this->repository.'/storage/app/public'), realpath($this->public.'/storage'));
    }

    public function test_an_existing_unmanaged_site_is_not_overwritten(): void
    {
        file_put_contents($this->public.'/index.php', 'existing site');
        try {
            (new CpanelDeployment($this->repository, $this->release))->publish();
            $this->fail('An unmanaged site should not be replaced.');
        } catch (DomainException) {
            $this->assertSame('existing site', file_get_contents($this->public.'/index.php'));
            $this->assertDirectoryDoesNotExist($this->public.'/assets');
        }
    }

    public function test_updates_keep_the_old_release_and_back_up_replaced_public_files(): void
    {
        (new CpanelDeployment($this->repository, $this->release))->publish();
        $second = $this->directory.'/troofe-releases/release-second';
        $this->makeRelease($second, 'second');
        (new CpanelDeployment($this->repository, $second))->publish();
        $this->assertSame('second', file_get_contents($this->public.'/assets/app.css'));
        $this->assertSame('first', file_get_contents($second.'/previous-public/assets/app.css'));
        $this->assertStringContainsString(var_export($this->release, true), file_get_contents($second.'/previous-public/index.php'));
        $this->assertStringContainsString(var_export($second, true), file_get_contents($this->public.'/index.php'));
        $this->assertDirectoryExists($this->release);
        $state = json_decode(file_get_contents($this->directory.'/troofe-releases/published.json'), true);
        $this->assertSame($this->release, $state['previous_release']);
    }

    public function test_a_symlink_cannot_redirect_a_public_write_outside_the_document_root(): void
    {
        (new CpanelDeployment($this->repository, $this->release))->publish();
        $outside = $this->directory.'/outside';
        file_put_contents($outside, 'untouched');
        symlink($outside, $this->public.'/assets/other.css');
        file_put_contents($this->release.'/public/assets/other.css', 'replacement');
        try {
            (new CpanelDeployment($this->repository, $this->release))->publish();
            $this->fail('A symlink destination should be rejected.');
        } catch (DomainException) {
            $this->assertSame('untouched', file_get_contents($outside));
        }
    }

    public function test_additional_php_scripts_are_not_exposed(): void
    {
        file_put_contents($this->release.'/public/extra.php', '<?php echo "private";');
        $this->expectException(DomainException::class);
        (new CpanelDeployment($this->repository, $this->release))->publish();
    }

    public function test_deployment_does_not_run_migrations_seeds_or_mail_workers(): void
    {
        $script = file_get_contents(base_path('deployment/cpanel.sh'));
        foreach (['migrate', 'db:seed', 'queue:work', 'schedule:run', '--ignore-platform'] as $command) {
            $this->assertStringNotContainsString($command, $script);
        }
        $this->assertStringContainsString('check-platform-reqs', $script);
        $this->assertStringContainsString('--no-scripts', $script);
        $this->assertStringContainsString('/bin/bash deployment/cpanel.sh', file_get_contents(base_path('.cpanel.yml')));
    }
}
