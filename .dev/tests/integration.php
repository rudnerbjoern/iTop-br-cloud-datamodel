<?php
/**
 * Verify the Cloud data model's native iTop and TeemIP contracts in the disposable installation.
 *
 * The environment dispatcher starts this file in a separate PHP process.
 */

declare(strict_types=1);

namespace BR\Cloud\Datamodel\Tests;

use AttributeExternalKey;
use Config;
use MetaModel;
use RuntimeException;

/**
 * Ensure both the runner and the installed iTop configuration target the reserved test database.
 *
 * @throws RuntimeException When the connection belongs to another environment.
 */
function requireTestDatabase(string $host, string $name, string $user): void
{
    if (getenv('ITOP_TEST_MODE') !== '1' || $host !== 'test-db' || $name !== 'itop_test' || $user !== 'itop_test') {
        throw new RuntimeException('This check requires the isolated iTop database.');
    }
}

requireTestDatabase((string) getenv('ITOP_TEST_DB_HOST'), (string) getenv('ITOP_TEST_DB_NAME'), (string) getenv('ITOP_TEST_DB_USER'));
$root = '/var/www/html/';
require $root . 'approot.inc.php';
require_once $root . 'core/config.class.inc.php';
$config = new Config($root . 'conf/production/config-itop.php');
requireTestDatabase($config->Get('db_host'), $config->Get('db_name'), $config->Get('db_user'));
require_once $root . 'application/startup.inc.php';

$installed = GetModulesInfo();
foreach (['br-cloud-datamodel', 'itop-virtualization-mgmt', 'itop-container-mgmt'] as $module) {
    if (!isset($installed[$module])) {
        throw new RuntimeException("Required module missing from compiled model: $module");
    }
}

$hasIpv6 = isset($installed['teemip-ipv6-mgmt']);
$hasBridge = isset($installed['br-cloud-datamodel-bridge-for-teemip']);
if ($hasBridge !== isset($installed['teemip-ip-mgmt'])) {
    throw new RuntimeException('The Cloud bridge must be installed exactly when TeemIP IP management is installed.');
}

$classes = [
    'CloudLan' => 'FunctionalCI', 'CloudNatGateway' => 'FunctionalCI',
    'Cloud' => 'VirtualHost', 'VirtualMachine' => 'VirtualDevice', 'LogicalInterface' => 'IPInterface',
    'ContainerCluster' => 'ContainerVirtualHost', 'ContainerHost' => 'ContainerVirtualHost',
    'ContainerApplication' => 'SoftwareInstance', 'ContainerImage' => 'cmdbAbstractObject',
];
$references = [
    ['CloudLan', 'cloud_id', 'Cloud', false],
    ['CloudNatGateway', 'cloud_id', 'Cloud', false],
    ['VirtualMachine', 'virtualhost_id', 'VirtualHost', false],
    ['LogicalInterface', 'virtualmachine_id', 'VirtualMachine', false],
    ['ContainerHost', 'system_id', 'FunctionalCI', false],
    ['ContainerHost', 'containercluster_id', 'ContainerCluster', true],
    ['ContainerApplication', 'containervirtualhost_id', 'ContainerVirtualHost', false],
    ['lnkContainerApplicationToImage', 'containerapplication_id', 'ContainerApplication', false],
    ['lnkContainerApplicationToImage', 'containerimage_id', 'ContainerImage', false],
];
if (isset($installed['teemip-ip-mgmt'])) {
    $classes += ['IPv4Address' => 'IPAddress', 'IPv4Subnet' => 'IPSubnet'];
    $references[] = ['IPv4Address', 'subnet_id', 'IPv4Subnet', true];
}
if ($hasIpv6) {
    $classes += ['IPv6Address' => 'IPAddress', 'IPv6Subnet' => 'IPSubnet'];
    $references[] = ['IPv6Address', 'subnet_id', 'IPv6Subnet', true];
}
if ($hasBridge) {
    $references[] = ['CloudNatGatewayLanAddress', 'ipaddress_id', 'IPAddress', false];
    $references[] = ['CloudNatGatewayPublicAddress', 'ipaddress_id', 'IPAddress', false];
    $references[] = ['lnkCloudToIPObject', 'cloud_id', 'Cloud', false];
    $references[] = ['lnkCloudToIPObject', 'ipobject_id', 'IPObject', false];
}
if (isset($installed['teemip-virtualization-mgmt-adaptor'])) {
    $references[] = ['VirtualMachine', 'managementip_id', 'IPAddress', true];
}
foreach ($classes as $class => $parent) {
    if (!MetaModel::IsValidClass($class) || !is_subclass_of($class, $parent)) {
        throw new RuntimeException("Expected $class to inherit from $parent.");
    }
}
foreach ($references as [$class, $field, $target, $nullable]) {
    $attribute = MetaModel::GetAttributeDef($class, $field);
    if (!($attribute instanceof AttributeExternalKey)
        || $attribute->GetTargetClass() !== $target || $attribute->IsNullAllowed() !== $nullable) {
        throw new RuntimeException("Unexpected compiled relation: $class.$field");
    }
}

foreach (['name', 'publisher', 'version', 'image'] as $field) {
    if (MetaModel::GetAttributeDef('ContainerImage', $field)->IsNullAllowed()) {
        throw new RuntimeException("Expected mandatory ContainerImage field: $field");
    }
}
echo "Compiled core and installed extension contracts passed.\n";

$provider = MetaModel::NewObject('Organization');
$provider->Set('name', 'Example cloud provider');
$provider->DBInsert();
$providerId = $provider->GetKey();
$details = MetaModel::FlattenZList(MetaModel::GetZListItems('Cloud', 'details'));
if (array_diff(['provider_resource_id', 'cloud_lans_list', 'cloud_nat_gateways_list'], $details)
    || in_array('ip_objects_list', $details, true) !== $hasBridge
    || MetaModel::IsValidAttCode('Cloud', 'ip_objects_list') !== $hasBridge
    || MetaModel::IsValidClass('lnkCloudToIPObject') !== $hasBridge) {
    throw new RuntimeException('The compiled Cloud fields and details form must match the installed modules.');
}
$cloud = MetaModel::NewObject('Cloud');
$cloud->Set('name', 'Inventory test VDC');
$cloud->Set('org_id', $providerId);
$cloud->DBInsert();
$cloud->Reload();
if ((string) $cloud->Get('provider_resource_id') !== '') {
    throw new RuntimeException('A new Cloud must keep an empty provider resource ID by default.');
}

$uuid = '6d947532-5782-48f4-a69a-e8858ce91871';
$cloud->Set('provider_resource_id', $uuid);
$cloud->DBUpdate();
$cloud->Reload();
if ($cloud->Get('provider_resource_id') !== $uuid) {
    throw new RuntimeException('Cloud provider resource ID did not survive the database round trip.');
}
if ($hasBridge) {
    if ($cloud->Get('ip_objects_list')->Count() !== 0) {
        throw new RuntimeException('A new Cloud must start with an empty IP object list.');
    }
    $families = ['IPv4' => ['192.0.2.0', '192.0.2.255', '255.255.255.128']];
    if ($hasIpv6) {
        $families['IPv6'] = ['2001:db8:abcd:1200::', '2001:db8:abcd:12ff:ffff:ffff:ffff:ffff', 64];
    }
    $links = [];
    $hostIds = [];
    foreach ($families as $family => [$start, $end, $mask]) {
        $block = MetaModel::NewObject($family . 'Block');
        $block->Set('org_id', $providerId);
        $block->Set('name', "Test $family block");
        $block->Set('firstip', $start);
        $block->Set('lastip', $end);
        $block->DBInsert();
        $subnet = MetaModel::NewObject($family . 'Subnet');
        $subnet->Set('org_id', $providerId);
        $subnet->Set('name', "Test $family subnet");
        $subnet->Set('block_id', $block->GetKey());
        $subnet->Set('ip', $start);
        $subnet->Set('mask', $mask);
        $subnet->DBInsert();
        $hosts = $family === 'IPv4' ? ['192.0.2.10', '192.0.2.11', '192.0.2.12'] : ['2001:db8:abcd:1200::11'];
        foreach ($hosts as $host) {
            $address = MetaModel::NewObject($family . 'Address');
            $address->Set('org_id', $providerId);
            $address->Set('subnet_id', $subnet->GetKey());
            $address->Set('ip', $host);
            $address->DBInsert();
            $hostIds[$host] = $address->GetKey();
        }
        foreach ([$block, $subnet] as $target) {
            $link = MetaModel::NewObject('lnkCloudToIPObject');
            $link->Set('cloud_id', $cloud->GetKey());
            $link->Set('ipobject_id', $target->GetKey());
            $link->DBInsert();
            $links[$target->GetKey()] = $link;
        }
    }
    $allowed = MetaModel::GetAllowedValuesAsObjectSet('lnkCloudToIPObject', 'ipobject_id');
    $candidates = $allowed->GetColumnAsArray('finalclass');
    if (array_diff_key($links, $candidates) || array_intersect_key($candidates, array_flip($hostIds))) {
        throw new RuntimeException('The IP object selector must offer blocks and subnets and exclude individual addresses.');
    }
    foreach ($candidates as $class) {
        if (!is_subclass_of($class, 'IPBlock') && !is_subclass_of($class, 'IPSubnet')) {
            throw new RuntimeException("Unexpected class in the IP object selector: $class");
        }
    }
    $cloud->Reload();
    $set = $cloud->Get('ip_objects_list');
    $ids = $set->GetColumnAsArray('ipobject_id', false);
    if (count($ids) !== count($links) || array_diff(array_keys($links), $ids)) {
        throw new RuntimeException('The Cloud list must retain every linked block and subnet.');
    }
    $first = reset($links);
    if ((string) $first->Get('source_key') !== '') {
        throw new RuntimeException('A manually created link must have an empty source key.');
    }
    $duplicate = MetaModel::NewObject('lnkCloudToIPObject');
    $duplicate->Set('cloud_id', $cloud->GetKey());
    $duplicate->Set('ipobject_id', $first->Get('ipobject_id'));
    $duplicate->Set('source_key', 'test-source');
    if ($duplicate->CheckToWrite()[0]) {
        throw new RuntimeException('A Cloud cannot have the same IP object twice.');
    }
    $first->DBDelete();
    $cloud->Reload();
    if ($cloud->Get('ip_objects_list')->Count() !== count($links) - 1
        || MetaModel::GetObject('IPObject', array_key_first($links), false) === null) {
        throw new RuntimeException('Unlinking must preserve the IP object and the remaining links.');
    }
}
echo "Cloud inventory and optional IP block and subnet relations passed.\n";

$secondCloud = MetaModel::NewObject('Cloud');
$secondCloud->Set('name', 'Second VDC for LAN inventory');
$secondCloud->Set('org_id', $providerId);
$secondCloud->DBInsert();
foreach ([$cloud, $secondCloud] as $parent) {
    $lan = MetaModel::NewObject('CloudLan');
    $lan->Set('cloud_id', $parent->GetKey());
    $lan->Set('org_id', $parent->Get('org_id'));
    $lan->Set('provider_resource_id', '1');
    $lan->Set('name', 'Shared LAN name');
    $lan->Set('public', $parent === $secondCloud);
    $lan->DBInsert();
    $parent->Reload();
    $children = $parent->Get('cloud_lans_list');
    if ($children->Count() !== 1 || (int) $children->Fetch()->GetKey() !== (int) $lan->GetKey()) {
        throw new RuntimeException('The LAN tab must list exactly the LAN belonging to its Cloud.');
    }
    $lan->Reload();
    if ((bool) $lan->Get('public') !== ($parent === $secondCloud)) {
        throw new RuntimeException('The LAN public flag did not survive the database round trip.');
    }
}
foreach ([$cloud, $secondCloud] as $parent) {
    $gateway = MetaModel::NewObject('CloudNatGateway');
    $gateway->Set('cloud_id', $parent->GetKey());
    $gateway->Set('org_id', $parent->Get('org_id'));
    $gateway->Set('provider_resource_id', 'gateway-1');
    $gateway->Set('name', 'Egress');
    $gateway->DBInsert();
    $gateway->Set('name', 'Renamed gateway');
    $gateway->DBUpdate();
    $parent->Reload();
    $children = $parent->Get('cloud_nat_gateways_list');
    $child = $children->Fetch();
    if ($children->Count() !== 1 || !$child || (int) $child->GetKey() !== (int) $gateway->GetKey()
        || $child->Get('provider_resource_id') !== 'gateway-1' || $child->Get('name') !== 'Renamed gateway') {
        throw new RuntimeException('A renamed NAT gateway must retain its identity and remain listed on its own Cloud.');
    }
}
$duplicateGateway = MetaModel::NewObject('CloudNatGateway');
$duplicateGateway->Set('name', 'Different gateway name');
$duplicateGateway->Set('org_id', $providerId);
$duplicateGateway->Set('provider_resource_id', 'gateway-1');
$duplicateGateway->Set('cloud_id', $cloud->GetKey());
if ($duplicateGateway->CheckToWrite()[0]) {
    throw new RuntimeException('Two NAT gateways in the same Cloud cannot share a provider resource ID.');
}

$duplicate = MetaModel::NewObject('CloudLan');
$duplicate->Set('name', 'Different display name');
$duplicate->Set('org_id', $providerId);
$duplicate->Set('provider_resource_id', '1');
$duplicate->Set('cloud_id', $cloud->GetKey());
if ($duplicate->CheckToWrite()[0]) {
    throw new RuntimeException('The same LAN ID cannot be reused in the same Cloud.');
}
echo "VDC-scoped LAN identity, visibility and reverse relation passed.\n";

// The last gateway and LAN above both belong to the second Cloud.
$attachment = MetaModel::NewObject('CloudNatGatewayLan');
$attachment->Set('nat_gateway_id', $gateway->GetKey());
$attachment->Set('cloud_lan_id', $lan->GetKey());
$attachment->DBInsert();
$gateway->Reload();
if ($gateway->Get('lan_attachments_list')->Count() !== 1) {
    throw new RuntimeException('A gateway must list its own LAN attachment.');
}
foreach (['CloudNatGatewayLanAddress', 'CloudNatGatewayPublicAddress'] as $class) {
    if (MetaModel::IsValidAttCode($class, 'ipaddress_id') !== $hasBridge
        || MetaModel::IsValidAttCode($class, 'address') === $hasBridge) {
        throw new RuntimeException("$class must expose exactly one host address field for this installation.");
    }
}
$hostField = $hasBridge ? 'ipaddress_id' : 'address';
$secondHost = (!$hasBridge || $hasIpv6) ? '2001:db8:abcd:1200::11' : '192.0.2.12';
foreach (['192.0.2.11' => 24, $secondHost => null] as $host => $prefix) {
    $hostValue = $hasBridge ? $hostIds[$host] : $host;
    $assignment = MetaModel::NewObject('CloudNatGatewayLanAddress');
    $assignment->Set('attachment_id', $attachment->GetKey());
    $assignment->Set($hostField, $hostValue);
    $assignment->Set('prefix_length', $prefix);
    $assignment->DBInsert();
    $assignment->Reload();
    if ((string) $assignment->Get($hostField) !== (string) $hostValue || $assignment->Get('prefix_length') !== $prefix) {
        throw new RuntimeException('The host address and reported prefix must survive a round trip.');
    }
}
$assignment->Set('prefix_length', 0);
$assignment->DBUpdate();
$assignment->Reload();
$attachment->Reload();
if ($attachment->Get('addresses_list')->Count() !== 2 || $assignment->Get('prefix_length') !== 0) {
    throw new RuntimeException('Updating a prefix to zero must preserve the address assignment.');
}
echo "Gateway LAN attachments and address assignments passed.\n";

$public = MetaModel::NewObject('CloudNatGatewayPublicAddress');
$public->Set('nat_gateway_id', $gateway->GetKey());
$publicHost = $hasBridge ? $hostIds['192.0.2.10'] : '192.0.2.10';
$public->Set($hostField, $publicHost);
$public->DBInsert();
$public->Reload();
$gateway->Reload();
if ((string) $public->Get($hostField) !== (string) $publicHost || $gateway->Get('public_addresses_list')->Count() !== 1) {
    throw new RuntimeException('A gateway must retain its public host address.');
}
$duplicate = MetaModel::NewObject('CloudNatGatewayPublicAddress');
$duplicate->Set('nat_gateway_id', $gateway->GetKey());
$duplicate->Set($hostField, $publicHost);
if ($duplicate->CheckToWrite()[0]) {
    throw new RuntimeException('Two public address assignments on a gateway cannot share a host address.');
}
echo "Public NAT gateway address identity and ownership passed.\n";
