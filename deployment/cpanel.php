<?php

use App\Support\CpanelDeployment;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

try {
    $mode = $argv[1] ?? '';
    $repository = $argv[2] ?? '';
    if (! in_array($mode, ['check', 'publish'], true) || $repository !== '/home3/troofeak/repositories/troofe') {
        throw new DomainException('Arguments de déploiement invalides.');
    }
    CpanelDeployment::validateEnvironment(Dotenv\Dotenv::parse(file_get_contents($repository.'/.env')));
    $app = require dirname(__DIR__).'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $deployment = new CpanelDeployment($repository, dirname(__DIR__));
    $deployment->assertReady();
    if ($mode === 'publish') {
        $deployment->publish();
    }
    fwrite(STDOUT, $mode === 'publish' ? "Fichiers publics publiés ; configuration privée conservée hors du Web.\n" : "Configuration et schéma vérifiés, sans migration ni envoi de mail.\n");
} catch (DomainException $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
} catch (Throwable) {
    fwrite(STDERR, "Déploiement interrompu. Vérifier les paramètres privés, les permissions et les logs Laravel, sans les publier.\n");
    exit(1);
}
