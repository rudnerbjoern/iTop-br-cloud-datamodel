<?php
/** Read and cross-check the version used by release automation. */

declare(strict_types=1);

const CLOUD_BASE_MODULE = 'br-cloud-datamodel';
const CLOUD_TEEMIP_BRIDGE = 'br-cloud-datamodel-bridge-for-teemip';
const CLOUD_RELEASE_MODULES = [CLOUD_BASE_MODULE, CLOUD_TEEMIP_BRIDGE];

/**
 * Verify the descriptor entries, all module registrations and internal dependencies.
 *
 * @param SimpleXMLElement $manifest Parsed extension descriptor.
 * @param array<string,string> $modules Module IDs mapped to registration file contents.
 * @return string Common numeric X.Y.Z release version.
 * @throws RuntimeException When a module, version or internal dependency is inconsistent.
 */
function releaseMetadataVersion(SimpleXMLElement $manifest, array $modules): string
{
    $version = trim((string) $manifest->version);
    if ($manifest->getName() !== 'extension' || (string) $manifest->extension_code !== CLOUD_BASE_MODULE
        || count($manifest->version) !== 1 || !preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/D', $version)
        || count($manifest->modules) !== 1 || (string) $manifest->modules['type'] !== 'array'
        || count($manifest->modules->module) !== count(CLOUD_RELEASE_MODULES)) {
        throw new RuntimeException('Invalid version or module entries in extension.xml.');
    }
    $ids = [];
    foreach ($manifest->modules->module as $entry) {
        $ids[] = (string) $entry->id;
        if ((string) $entry->version !== $version) {
            throw new RuntimeException('Module version differs from the descriptor version.');
        }
    }
    sort($ids);
    $provided = array_keys($modules);
    sort($provided);
    if ($ids !== CLOUD_RELEASE_MODULES || $provided !== CLOUD_RELEASE_MODULES) {
        throw new RuntimeException('Expected the Cloud base and the TeemIP bridge.');
    }
    foreach ($modules as $name => $source) {
        $pattern = "~SetupWebPage::AddModule\\(\\s*__FILE__,\\s*'" . preg_quote($name, '~') . "/([^']+)'~";
        if (preg_match_all($pattern, $source, $matches) !== 1 || $matches[1][0] !== $version) {
            throw new RuntimeException("Module registration must match the descriptor for $name.");
        }
    }
    $basePattern = "~'" . preg_quote(CLOUD_BASE_MODULE, '~') . "/([^']+)'~";
    if (!preg_match("~'dependencies'\\s*=>\\s*\\[([^\\]]*)\\]~s", $modules[CLOUD_TEEMIP_BRIDGE], $dependencies)
        || preg_match_all($basePattern, $dependencies[1], $matches) !== 1 || $matches[1][0] !== $version) {
        throw new RuntimeException("The TeemIP bridge must depend on the bundled Cloud base version.");
    }
    return $version;
}

/**
 * Read the common version from the descriptor and all registered runtime modules.
 *
 * @param string $root Repository root containing extension.xml.
 * @return string Validated semantic release version.
 * @throws RuntimeException When a file cannot be read or its metadata is inconsistent.
 */
function projectVersion(string $root): string
{
    $manifest = simplexml_load_file($root . '/extension.xml', SimpleXMLElement::class, LIBXML_NONET);
    if ($manifest === false) {
        throw new RuntimeException('Cannot read the extension descriptor.');
    }
    $modules = [];
    foreach (CLOUD_RELEASE_MODULES as $name) {
        $source = file_get_contents("$root/$name/module.$name.php");
        if ($source === false) {
            throw new RuntimeException("Cannot read the registration for $name.");
        }
        $modules[$name] = $source;
    }
    return releaseMetadataVersion($manifest, $modules);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $version = projectVersion(dirname(__DIR__));
    if (($argv[1] ?? '') !== '' && $argv[1] !== 'release/' . $version) {
        throw new RuntimeException('Release branch and project version must match.');
    }
    echo $version, PHP_EOL;
}
