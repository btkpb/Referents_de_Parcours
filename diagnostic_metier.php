<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Entity\MetierCatalogue;
use Doctrine\ORM\Mapping\Table;

$kernel = new Kernel('dev', true);
$kernel->boot();

try {
    $reflection = new ReflectionClass(MetierCatalogue::class);

    echo "\n--- Attribut Table lu directement par PHP ---\n";
    $attributes = $reflection->getAttributes(Table::class);

    if (!$attributes) {
        echo "AUCUN attribut Table trouve.\n";
    } else {
        foreach ($attributes as $attribute) {
            var_dump($attribute->newInstance());
        }
    }

    echo "\n--- Metadonnees chargees par Doctrine ---\n";
    $em = $kernel->getContainer()
        ->get('doctrine')
        ->getManager();

    $metadata = $em->getClassMetadata(MetierCatalogue::class);

    var_export($metadata->table);
    echo "\n";
} catch (Throwable $e) {
    echo "\nERREUR : " . get_class($e) . "\n";
    echo $e->getMessage() . "\n";
} finally {
    $kernel->shutdown();
}
