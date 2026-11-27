<?php
/** English labels for Cloud network inventory links to TeemIP objects. */

Dict::Add('EN US', 'English', 'English', [
    'Class:Cloud/Attribute:ip_objects_list' => 'IP blocks and subnets',
    'Class:Cloud/Attribute:ip_objects_list+' => 'TeemIP blocks and subnets associated with this Cloud.',
    'Class:lnkCloudToIPObject' => 'Cloud to IP object',
    'Class:lnkCloudToIPObject+' => 'Association between a Cloud and a TeemIP block or subnet.',
    'Class:lnkCloudToIPObject/Attribute:cloud_id' => 'Cloud',
    'Class:lnkCloudToIPObject/Attribute:ipobject_id' => 'IP block or subnet',
    'Class:lnkCloudToIPObject/Attribute:source_key' => 'Source key',
    'Class:lnkCloudToIPObject/Attribute:source_key+' => 'Identifier of the source managing this association. Empty for manual links.',
    'Class:lnkCloudToIPObject/UniquenessRule:no_duplicate' => 'This IP object is already linked to the Cloud.',
    'Class:lnkCloudToIPObject/UniquenessRule:no_duplicate+' => 'Each block or subnet can be linked only once to a given Cloud.',
    'Class:CloudNatGatewayLanAddress/Attribute:ipaddress_id' => 'Host address',
    'Class:CloudNatGatewayLanAddress/Attribute:ipaddress_id+' => 'IP address in the organization that owns this gateway.',

    'Class:CloudNatGatewayPublicAddress/Attribute:ipaddress_id' => 'Host address',
    'Class:CloudNatGatewayPublicAddress/Attribute:ipaddress_id+' => 'IP address in the organization that owns this gateway.',

    'Class:CloudNetworkLoadBalancerListenerAddress/Attribute:ipaddress_id' => 'Host address',
    'Class:CloudNetworkLoadBalancerBackendAddress/Attribute:ipaddress_id' => 'Host address',
    'Class:CloudVpnGateway/Attribute:public_endpoint_id' => 'Public endpoint',
    'Class:CloudVpnGatewayLanAddress/Attribute:ipaddress_id' => 'LAN-side host address',
    'Class:CloudVpnGatewayInterfaceAddress/Attribute:ipaddress_id' => 'VPN interface host address',
]);
