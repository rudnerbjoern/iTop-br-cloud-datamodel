<?php
/** Validate the bundle descriptor, module registration and iTop data model schemas. */

declare(strict_types=1);

namespace BR\Cloud\Datamodel\Tests;

use DOMDocument;
use RuntimeException;
use SimpleXMLElement;

require_once __DIR__ . '/../../scripts/release-version.php';

$root = dirname(__DIR__, 2);
$schemas = getenv('ITOP_SCHEMA_DIR') ?: $root . '/.tmp/schemas';
$name = 'br-cloud-datamodel';
$bridge = 'br-cloud-datamodel-bridge-for-teemip';
$version = projectVersion($root);
foreach (['extension.xml' => 'extension.xsd', "$name/datamodel.$name.xml" => '3.3/itop_design.xsd',
    "$bridge/datamodel.$bridge.xml" => '3.3/itop_design.xsd'] as $file => $schema) {
    $xml = new DOMDocument();
    if (!$xml->load("$root/$file", LIBXML_NONET) || !$xml->schemaValidate("$schemas/$schema")) {
        throw new RuntimeException("Schema validation failed for $file using $schemas/$schema");
    }
}
/** Capture the real setup registration during a native check without loading the iTop runtime. */
final class RegistrationCapture
{
    /** @var array<string,mixed> Module metadata passed to the installer. */
    public static array $metadata = [];

    /**
     * Save the metadata for the registered module.
     *
     * @param array<string,mixed> $metadata Setup parameters for the module.
     */
    public static function AddModule(string $file, string $id, array $metadata): void
    {
        self::$metadata = $metadata;
    }
}

class_alias(RegistrationCapture::class, 'SetupWebPage');
require "$root/$name/module.$name.php";
$required = ['itop-virtualization-mgmt/3.3.0', 'itop-container-mgmt/3.3.0'];
$metadata = RegistrationCapture::$metadata;
if ($metadata['dependencies'] !== $required || $metadata['data.struct'] !== []
    || $metadata['data.sample'] !== []) {
    throw new RuntimeException('Unexpected dependencies or sample registration.');
}
require "$root/$bridge/module.$bridge.php";
$bridgeMetadata = RegistrationCapture::$metadata;
if ($bridgeMetadata['dependencies'] !== ["$name/$version", 'teemip-ip-mgmt/3.3.1 || !(teemip-ip-mgmt/0.0.0)']
    || $bridgeMetadata['mandatory'] !== false || empty($bridgeMetadata['auto_select'])) {
    throw new RuntimeException('Unexpected optional bridge registration.');
}
$base = simplexml_load_file("$root/$name/datamodel.$name.xml", SimpleXMLElement::class, LIBXML_NONET);
foreach (['CloudLan', 'CloudNatGateway'] as $class) {
    $badges = $base->xpath("menus/menu[@id='ConfigManagementOverview']/definition/cells/cell[@id='1']"
        . "/dashlets/dashlet[class='$class']");
    if (count($badges) !== 1 || (string) $badges[0]->attributes('xsi', true)->type !== 'DashletBadge') {
        throw new RuntimeException("Missing or duplicate virtualization badge for $class.");
    }
}
$gateway = $base->xpath("classes/class[@id='CloudNatGateway' and parent='FunctionalCI']")[0] ?? null;
$cloud = $base->xpath("classes/class[@id='Cloud']")[0];
$gateways = $cloud->xpath("fields/field[@id='cloud_nat_gateways_list']")[0] ?? null;
if ($gateway === null || $gateways === null
    || (string) $gateways->attributes('xsi', true)->type !== 'AttributeLinkedSet'
    || (string) $gateways->linked_class !== 'CloudNatGateway' || (string) $gateways->ext_key_to_me !== 'cloud_id'
    || !$cloud->xpath("presentation/details/items/item[@id='cloud_nat_gateways_list']")) {
    throw new RuntimeException('The Cloud details must list its NAT gateways through their Cloud reference.');
}
$parent = $gateway->xpath("fields/field[@id='cloud_id' and target_class='Cloud' and is_null_allowed='false']")[0] ?? null;
$id = $gateway->xpath("fields/field[@id='provider_resource_id' and is_null_allowed='false']")[0] ?? null;
if ($parent === null || (string) $parent->attributes('xsi', true)->type !== 'AttributeExternalKey'
    || $id === null || (string) $id->attributes('xsi', true)->type !== 'AttributeString') {
    throw new RuntimeException('A NAT gateway requires a Cloud reference and a provider resource ID.');
}
foreach (['reconciliation', "uniqueness_rules/rule[@id='gateway_id_in_cloud' and is_blocking='true']"] as $path) {
    $codes = array_map('strval', $gateway->xpath("properties/$path/attributes/attribute/@id"));
    if ($codes !== ['cloud_id', 'provider_resource_id']) {
        throw new RuntimeException('NAT gateway identity must be scoped to its Cloud.');
    }
}

$model = simplexml_load_file("$root/$bridge/datamodel.$bridge.xml", SimpleXMLElement::class, LIBXML_NONET);
$list = $model->xpath("classes/class[@id='Cloud']/fields/field[@id='ip_objects_list']")[0] ?? null;
$link = $model->xpath("classes/class[@id='lnkCloudToIPObject']")[0] ?? null;
if ($list === null || $link === null || (string) $list->attributes('xsi', true)->type !== 'AttributeLinkedSetIndirect'
    || (string) $list->linked_class !== 'lnkCloudToIPObject' || (string) $list->ext_key_to_me !== 'cloud_id'
    || (string) $list->ext_key_to_remote !== 'ipobject_id') {
    throw new RuntimeException('Unexpected Cloud IP object list.');
}
foreach (['cloud_id' => 'Cloud', 'ipobject_id' => 'IPObject'] as $code => $target) {
    $key = $link->xpath("fields/field[@id='$code']")[0] ?? null;
    if ($key === null || (string) $key->attributes('xsi', true)->type !== 'AttributeExternalKey'
        || (string) $key->target_class !== $target || (string) $key->is_null_allowed !== 'false') {
        throw new RuntimeException("Unexpected Cloud IP object link endpoint: $code");
    }
}
$sourceKey = $link->xpath("fields/field[@id='source_key']")[0] ?? null;
if ($sourceKey === null || (string) $sourceKey->attributes('xsi', true)->type !== 'AttributeString'
    || (string) $sourceKey->is_null_allowed !== 'true' || (string) $sourceKey->default_value !== '') {
    throw new RuntimeException('Manual IP object links must allow an empty source key.');
}
$filter = 'SELECT IPObject WHERE id IN (SELECT IPBlock) OR id IN (SELECT IPSubnet)';
$remote = $link->xpath("fields/field[@id='ipobject_id']")[0];
foreach ([$list, $remote] as $selector) {
    if (preg_replace('/\s+/', ' ', trim((string) $selector->filter)) !== $filter) {
        throw new RuntimeException('Both IP object selectors must restrict choices to blocks and subnets.');
    }
}
$rules = $link->xpath("properties/uniqueness_rules/rule[is_blocking='true']/attributes");
$pairRule = false;
foreach ($rules as $attributes) {
    $codes = array_map('strval', $attributes->xpath('attribute/@id'));
    sort($codes);
    if ($codes === ['cloud_id', 'ipobject_id']) {
        $pairRule = true;
    }
}
if (!$pairRule) {
    throw new RuntimeException('The Cloud and IP object must form a unique pair.');
}
$attachment = 'CloudNatGatewayLan';
$assignment = 'CloudNatGatewayLanAddress';
$publicAddress = 'CloudNatGatewayPublicAddress';
foreach ([
    [$gateway, 'lan_attachments_list', $attachment, 'nat_gateway_id'],
    [$gateway, 'public_addresses_list', $publicAddress, 'nat_gateway_id'],
    [$base->xpath("classes/class[@id='$attachment']")[0] ?? null, 'addresses_list', $assignment, 'attachment_id'],
] as [$owner, $field, $child, $foreignKey]) {
    $set = $owner?->xpath("fields/field[@id='$field']")[0] ?? null;
    if ($set === null || (string) $set->attributes('xsi', true)->type !== 'AttributeLinkedSet'
        || (string) $set->linked_class !== $child || (string) $set->ext_key_to_me !== $foreignKey
        || !$owner->xpath("presentation/details//item[@id='$field']")) {
        throw new RuntimeException("Missing navigable gateway child list: $field.");
    }
}

foreach ([$attachment => ['nat_gateway_id' => 'CloudNatGateway', 'cloud_lan_id' => 'CloudLan'],
    $assignment => ['attachment_id' => $attachment, 'address' => 'AttributeString'],
    $publicAddress => ['nat_gateway_id' => 'CloudNatGateway', 'address' => 'AttributeString']] as $class => $identity) {
    $node = $base->xpath("classes/class[@id='$class']")[0];
    foreach ($identity as $code => $target) {
        $field = $node->xpath("fields/field[@id='$code' and is_null_allowed='false']")[0] ?? null;
        $type = $code === 'address' ? 'AttributeString' : 'AttributeExternalKey';
        if ($field === null || (string) $field->attributes('xsi', true)->type !== $type
            || ($type === 'AttributeExternalKey' && ((string) $field->target_class !== $target
                || (string) $field->on_target_delete !== 'DEL_MANUAL'))) {
            throw new RuntimeException("Unexpected field in gateway inventory identity: $class.$code.");
        }
    }
    foreach (['reconciliation', "uniqueness_rules/rule[is_blocking='true']"] as $path) {
        $codes = array_map('strval', $node->xpath("properties/$path/attributes/attribute/@id"));
        if ($codes !== array_keys($identity)) {
            throw new RuntimeException("Unexpected identity in $class $path.");
        }
    }
}
$prefix = $base->xpath("classes/class[@id='$assignment']/fields/field[@id='prefix_length']")[0] ?? null;
if ($prefix === null || (string) $prefix->attributes('xsi', true)->type !== 'AttributeInteger'
    || (string) $prefix->is_null_allowed !== 'true') {
    throw new RuntimeException('A gateway address must distinguish an unknown prefix from prefix zero.');
}
foreach ([$assignment, $publicAddress] as $class) {
    $addressField = "classes/class[@id='$class']/fields/field[@id='ipaddress_id']";
    $ipam = $model->xpath($addressField)[0] ?? null;
    $node = $model->xpath("classes/class[@id='$class']")[0];
    if (!$node->xpath("fields/field[@id='address' and @_delta='delete']")
        || !$node->xpath("properties/naming/attributes/attribute[@id='ipaddress_id_friendlyname' and @_delta='define']")
        || !$node->xpath("properties/reconciliation/attributes/attribute[@id='ipaddress_id' and @_delta='define']")
        || !$node->xpath("properties/uniqueness_rules/rule/attributes/attribute[@id='ipaddress_id' and @_delta='define']")
        || $node->xpath("presentation/list//item[@id='ipaddress_id']")
        || $base->xpath("classes/class[@id='$class']/presentation/list//item[@id='address']")
        || $base->xpath($addressField) || $ipam === null || (string) $ipam->target_class !== 'IPAddress'
        || (string) $ipam->attributes('xsi', true)->type !== 'AttributeExternalKey'
        || (string) $ipam->is_null_allowed !== 'false'
        || (string) $ipam->filter !== 'SELECT IPAddress WHERE org_id = :this->org_id'
        || !$model->xpath("classes/class[@id='$class']/presentation/details//item[@id='ipaddress_id']")) {
        throw new RuntimeException('The bridge must replace the NAT address throughout the model with its required IPAM reference.');
    }
}
$nlbClass = 'CloudNetworkLoadBalancer';
$nlb = $base->xpath("classes/class[@id='$nlbClass' and parent='FunctionalCI']")[0] ?? null;
$nlbList = $cloud->xpath("fields/field[@id='cloud_network_load_balancers_list']")[0] ?? null;
if ($nlb === null || (string) $nlbList?->linked_class !== $nlbClass || (string) $nlbList?->ext_key_to_me !== 'cloud_id'
    || !$cloud->xpath("presentation/details/items/item[@id='cloud_network_load_balancers_list']")) {
    throw new RuntimeException('Each Cloud must list its network load balancers.');
}
foreach (['reconciliation', 'uniqueness_rules/rule[is_blocking="true"]'] as $path) {
    if (array_map('strval', $nlb->xpath("properties/$path/attributes/attribute/@id")) !== ['cloud_id', 'provider_resource_id']) {
        throw new RuntimeException('The load balancer ID must be scoped to its Cloud.');
    }
}
foreach (['listener', 'backend'] as $role) {
    $class = $nlbClass . ucfirst($role) . 'Address';
    $lan = $nlb->xpath("fields/field[@id='{$role}_lan_id' and target_class='CloudLan' and is_null_allowed='false']")[0] ?? null;
    $list = $nlb->xpath("fields/field[@id='{$role}_addresses_list']")[0] ?? null;
    if ((string) $lan?->filter !== 'SELECT CloudLan WHERE cloud_id = :this->cloud_id'
        || (string) $list?->linked_class !== $class || (string) $list?->ext_key_to_me !== 'load_balancer_id'
        || !$nlb->xpath("presentation/details/items/item[@id='{$role}_addresses_list']")) {
        throw new RuntimeException("Invalid load balancer LAN or address list for $role.");
    }
    $assignment = $base->xpath("classes/class[@id='$class']")[0];
    $ipam = $model->xpath("classes/class[@id='$class']")[0];
    foreach (['reconciliation', 'uniqueness_rules/rule'] as $path) {
        if (array_map('strval', $assignment->xpath("properties/$path/attributes/attribute/@id")) !== ['load_balancer_id', 'address']
            || !$ipam->xpath("properties/$path/attributes/attribute[@id='ipaddress_id' and @_delta='define']")) {
            throw new RuntimeException("Invalid address identity for $class.");
        }
    }
    if (!$ipam->xpath("fields/field[@id='address' and @_delta='delete']")
        || !$ipam->xpath("fields/field[@id='ipaddress_id' and target_class='IPAddress' and on_target_delete='DEL_MANUAL']")) {
        throw new RuntimeException("The TeemIP bridge must replace the host literal for $class.");
    }
}
if (!$base->xpath("classes/class[@id='{$nlbClass}BackendAddress']/fields/field[@id='prefix_length']")
    || $model->xpath("classes/class[@id='{$nlbClass}BackendAddress']/fields/field[@id='prefix_length']")) {
    throw new RuntimeException('The backend prefix must be stored independently of the address representation.');
}
echo "Cloud module registration, network inventory, IP object links and XML schemas passed.\n";
