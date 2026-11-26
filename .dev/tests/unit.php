<?php
/** Exercise release preparation and the bridge dependency pin in memory. */

declare(strict_types=1);

namespace BR\Cloud\Datamodel\Tests;

use RuntimeException;
use SimpleXMLElement;

require_once __DIR__ . '/../../scripts/prepare-release.php';

$root = dirname(__DIR__, 2);
$version = projectVersion($root);
$manifest = file_get_contents($root . '/extension.xml');
$modules = [];
foreach (CLOUD_RELEASE_MODULES as $id) {
    $modules[$id] = file_get_contents("$root/$id/module.$id.php");
}
[$major, $minor, $patch] = explode('.', $version);
$next = "$major.$minor." . ((int) $patch + 1);
[$updated, $registrations] = prepareReleaseContents($manifest, $modules, $next, '2028-02-29');
$xml = simplexml_load_string($updated, SimpleXMLElement::class, LIBXML_NONET);
if ($xml === false || releaseMetadataVersion($xml, $registrations) !== $next
    || (string) $xml->release_date !== '2028-02-29') {
    throw new RuntimeException('Release preparation did not update the bundle metadata.');
}
foreach ($modules as $id => $original) {
    $restored = str_replace("'$id/$next'", "'$id/$version'", $registrations[$id], $registrationsChanged);
    if ($registrationsChanged !== 1) {
        throw new RuntimeException("Expected exactly one updated $id registration.");
    }
    if ($id !== CLOUD_BASE_MODULE) {
        $base = CLOUD_BASE_MODULE;
        $restored = str_replace("'$base/$next'", "'$base/$version'", $restored, $pinsChanged);
        if ($pinsChanged !== 1) {
            throw new RuntimeException('Expected exactly one updated bridge dependency pin.');
        }
    }
    if ($restored !== $original) {
        throw new RuntimeException("Unexpected release changes in $id.");
    }
}

$stale = $modules;
$base = CLOUD_BASE_MODULE;
$stale[CLOUD_TEEMIP_BRIDGE] = str_replace("'$base/$version'", "'$base/$next'", $stale[CLOUD_TEEMIP_BRIDGE]);
foreach (['a missing bridge' => [$base => $modules[$base]], 'a stale base dependency' => $stale] as $label => $invalid) {
    try {
        prepareReleaseContents($manifest, $invalid, $next, '2028-02-29');
    } catch (RuntimeException) {
        continue;
    }
    throw new RuntimeException("Release preparation accepted $label.");
}
echo "Release preparation for all modules and their shared version passed.\n";
