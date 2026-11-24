<?php

namespace App\Support;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class CpanelDeployment
{
    public function __construct(private string $repository, private string $release) {}

    public static function validateEnvironment(array $env): void
    {
        foreach (['APP_KEY', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS'] as $key) {
            if (trim($env[$key] ?? '') === '') {
                throw new DomainException("Configurer $key dans le fichier privé .env.");
            }
        }
        if (($env['APP_ENV'] ?? '') !== 'production' || ! in_array($env['APP_DEBUG'] ?? '', ['false', '0'], true)) {
            throw new DomainException('APP_ENV doit être production et APP_DEBUG doit être false.');
        }
        if (rtrim($env['APP_URL'] ?? '', '/') !== 'https://troofe.net') {
            throw new DomainException('APP_URL doit être https://troofe.net, sans /fr.');
        }
        if (! in_array($env['SESSION_SECURE_COOKIE'] ?? '', ['true', '1'], true)) {
            throw new DomainException('SESSION_SECURE_COOKIE doit être true.');
        }
        $key = $env['APP_KEY'];
        if (! str_starts_with($key, 'base64:') || strlen(base64_decode(substr($key, 7), true) ?: '') !== 32) {
            throw new DomainException('APP_KEY doit être une clé Laravel AES-256 valide, à conserver entre les déploiements.');
        }
        if (($env['DB_CONNECTION'] ?? '') !== 'mysql' || ($env['DB_USERNAME'] ?? '') === 'root') {
            throw new DomainException('Configurer la base MySQL et son utilisateur cPanel dédié.');
        }
        if (($env['MAIL_MAILER'] ?? '') !== 'smtp' || ($env['QUEUE_CONNECTION'] ?? '') !== 'database') {
            throw new DomainException('Configurer SMTP et QUEUE_CONNECTION=database avant publication.');
        }
        $driver = $env['PAYMENT_DRIVER'] ?? 'simulation';
        if (! in_array($driver, ['simulation', 'cinetpay'], true)) {
            throw new DomainException('PAYMENT_DRIVER inconnu.');
        }
        if ($driver === 'cinetpay') {
            foreach (['CINETPAY_API_KEY', 'CINETPAY_SITE_ID', 'CINETPAY_SECRET_KEY'] as $key) {
                if (trim($env[$key] ?? '') === '') {
                    throw new DomainException("Configurer $key avant d’activer CinetPay.");
                }
            }
        }
    }

    public function assertReady(): void
    {
        $this->assertLayout();
        $environment = \Dotenv\Dotenv::parse(file_get_contents($this->repository.'/.env'));
        self::validateEnvironment($environment);
        if (! app()->isProduction() || config('app.debug') || rtrim(config('app.url'), '/') !== 'https://troofe.net' || ! config('session.secure')
            || config('app.key') !== $environment['APP_KEY'] || config('mail.default') !== 'smtp' || config('queue.default') !== 'database'
            || config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== $environment['DB_DATABASE']
            || config('database.connections.mysql.username') !== $environment['DB_USERNAME']) {
            throw new DomainException('La configuration Laravel chargée ne correspond pas aux paramètres de production.');
        }
        try {
            DB::connection()->getPdo();
            $migrator = app('migrator');
            $ready = $migrator->repositoryExists() && ! array_diff(
                array_keys($migrator->getMigrationFiles(database_path('migrations'))),
                $migrator->getRepository()->getRan(),
            );
            if (! $ready) {
                throw new DomainException('Base non initialisée ou migrations en attente. Les appliquer séparément après validation ; aucune migration automatique n’a été exécutée.');
            }
            if (! DB::table('plans')->where('slug', 'decouverte')->where('is_active', true)->exists()) {
                throw new DomainException('Initialiser les formules de production avec PlanSeeder uniquement, sans DatabaseSeeder de démonstration.');
            }
            foreach (User::where('is_active', true)->whereJsonContains('roles', 'admin')->cursor() as $admin) {
                if (Hash::check('demo1234', $admin->password)) {
                    throw new DomainException('Remplacer les accès administrateur de démonstration avant publication.');
                }
            }
        } catch (DomainException $error) {
            throw $error;
        } catch (\Throwable) {
            throw new DomainException('Vérification de la base impossible. Contrôler les paramètres privés et les droits MySQL dans cPanel.');
        }
    }

    private function assertLayout(): void
    {
        $home = dirname($this->repository, 2);
        if (realpath($this->repository) !== $this->repository || basename(dirname($this->repository)) !== 'repositories'
            || basename($this->repository) !== 'troofe' || realpath($this->release) !== $this->release
            || dirname($this->release) !== $home.'/troofe-releases' || ! str_starts_with(basename($this->release), 'release-')
            || ! is_dir($home.'/public_html') || is_link($home.'/public_html') || is_link($home.'/troofe-releases')) {
            throw new DomainException('Disposition des répertoires cPanel inattendue ; publication interrompue.');
        }
    }

    public function publish(): void
    {
        $this->assertLayout();
        $home = dirname($this->repository, 2);
        $public = $home.'/public_html';
        $statePath = $home.'/troofe-releases/published.json';
        $lock = fopen($home.'/troofe-releases/publish.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new DomainException('Une autre publication est déjà en cours.');
        }
        try {
            $previous = is_file($statePath) ? json_decode(file_get_contents($statePath), true, flags: JSON_THROW_ON_ERROR) : null;
            $owned = $previous['files'] ?? [];
            if ($previous === null && array_diff(scandir($public), ['.', '..', '.well-known'])) {
                throw new DomainException('public_html n’est pas vide : sauvegarder et valider son contenu avant la première publication.');
            }
            $files = [];
            $source = $this->release.'/public';
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS)) as $file) {
                $relative = substr($file->getPathname(), strlen($source) + 1);
                if ($relative === 'storage' || str_starts_with($relative, 'storage/')) {
                    continue;
                }
                if ($file->isLink()) {
                    throw new DomainException('Lien symbolique inattendu dans les fichiers à publier.');
                }
                if (! $file->isFile() || ($relative !== '.htaccess' && preg_match('~(^|/)\.~', $relative))) {
                    continue;
                }
                if (preg_match('/\.(?:php\d*|phtml|phar|pht|shtml|cgi|pl|py|sh)(?:\.|$)/i', $relative) && $relative !== 'index.php') {
                    throw new DomainException('Fichier exécutable inattendu dans le dossier public.');
                }
                $destination = $public.'/'.$relative;
                $this->assertDestination($public, $relative);
                if (file_exists($destination) && ! in_array($relative, $owned, true)) {
                    throw new DomainException('Un fichier public non géré serait écrasé ; publication interrompue.');
                }
                $files[$relative] = $file->getPathname();
            }
            if (! isset($files['index.php'], $files['.htaccess'])) {
                throw new DomainException('Point d’entrée ou configuration Apache manquants.');
            }
            $storage = $this->repository.'/storage/app/public';
            if (is_link($public.'/storage') ? realpath($public.'/storage') !== realpath($storage) : file_exists($public.'/storage')) {
                throw new DomainException('Le chemin public storage est déjà occupé par un autre contenu.');
            }
            foreach ($files as $relative => $sourceFile) {
                if (is_file($public.'/'.$relative)) {
                    $backup = $this->release.'/previous-public/'.$relative;
                    $this->directory(dirname($backup), 0700);
                    if (! copy($public.'/'.$relative, $backup) || ! chmod($backup, 0600)) {
                        throw new DomainException('Sauvegarde des fichiers publics impossible.');
                    }
                }
            }
            $entry = $this->release.'/deployment/front-controller.php';
            if (file_put_contents($entry, $this->frontController()) === false) {
                throw new DomainException('Préparation du point d’entrée impossible.');
            }
            unset($files['index.php']);
            foreach ($files as $relative => $sourceFile) {
                $this->install($sourceFile, $public.'/'.$relative);
            }
            if (! is_link($public.'/storage') && ! symlink($storage, $public.'/storage')) {
                throw new DomainException('Création du lien public vers les médias impossible.');
            }
            $this->install($entry, $public.'/index.php');
            $files['index.php'] = $entry;
            $state = json_encode(['release' => $this->release, 'previous_release' => $previous['release'] ?? null, 'files' => array_values(array_unique(array_merge($owned, array_keys($files))))], JSON_THROW_ON_ERROR);
            $temporary = $statePath.'.'.bin2hex(random_bytes(6));
            if (file_put_contents($temporary, $state) === false || ! chmod($temporary, 0600) || ! rename($temporary, $statePath)) {
                throw new DomainException('Enregistrement de la publication impossible ; conserver la version et ses sauvegardes pour vérification.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function assertDestination(string $public, string $relative): void
    {
        $path = $public;
        foreach (explode('/', $relative) as $part) {
            $path .= '/'.$part;
            if (is_link($path) || (file_exists($path) && ! is_dir($path) && $path !== $public.'/'.$relative)) {
                throw new DomainException('Chemin public occupé ou lien symbolique inattendu.');
            }
        }
    }

    private function directory(string $path, int $mode = 0755): void
    {
        if (! is_dir($path) && ! mkdir($path, $mode, true)) {
            throw new DomainException('Création d’un répertoire de déploiement impossible.');
        }
    }

    private function install(string $source, string $destination): void
    {
        $this->directory(dirname($destination));
        $temporary = $this->release.'/deployment/publish-'.bin2hex(random_bytes(8));
        if (! copy($source, $temporary) || ! chmod($temporary, 0644) || ! rename($temporary, $destination)) {
            throw new DomainException('Installation d’un fichier public impossible ; consulter les sauvegardes de la version.');
        }
    }

    private function frontController(): string
    {
        $root = var_export($this->release, true);

        return "<?php\n\nuse Illuminate\\Http\\Request;\n\ndefine('LARAVEL_START', microtime(true));\n\n\$root = $root;\nif (file_exists(\$maintenance = \$root.'/storage/framework/maintenance.php')) {\n    require \$maintenance;\n}\nrequire \$root.'/vendor/autoload.php';\n\$app = require \$root.'/bootstrap/app.php';\n\$app->usePublicPath(__DIR__);\n\$app->handleRequest(Request::capture());\n";
    }
}
