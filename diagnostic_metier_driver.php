<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\MetierCatalogue;

$kernel = new Kernel('dev', true);
$kernel->boot();

try {
    $em = $kernel->getContainer()
        ->get('doctrine')
        ->getManager();

    $driver = $em->getConfiguration()->getMetadataDriverImpl();

    echo "Pilote : " . get_class($driver) . "\n";

    if (method_exists($driver, 'getDrivers')) {
        echo "\nPilotes associes :\n";
        var_export($driver->getDrivers());
        echo "\n";
    }

    echo "\nClasses detectees pour App\\Entity :\n";
    if (method_exists($driver, 'getAllClassNames')) {
        foreach ($driver->getAllClassNames() as $className) {
            if (str_contains($className, 'MetierCatalogue')) {
                echo $className . "\n";
            }
        }
    }

    echo "\nMetadonnees de la table :\n";
    var_export($em->getClassMetadata(MetierCatalogue::class)->table);
    echo "\n";
} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
} finally {
    $kernel->shutdown();
}
