<?php
/** German labels for Cloud network inventory links to TeemIP objects. */

Dict::Add('DE DE', 'German', 'Deutsch', [
    'Class:Cloud/Attribute:ip_objects_list' => 'IP-Blöcke und Subnetze',
    'Class:Cloud/Attribute:ip_objects_list+' => 'TeemIP-Blöcke und -Subnetze, die dieser Cloud zugeordnet sind.',
    'Class:lnkCloudToIPObject' => 'Cloud zu IP-Objekt',
    'Class:lnkCloudToIPObject+' => 'Zuordnung einer Cloud zu einem TeemIP-Block oder -Subnetz.',
    'Class:lnkCloudToIPObject/Attribute:cloud_id' => 'Cloud',
    'Class:lnkCloudToIPObject/Attribute:ipobject_id' => 'IP-Block oder Subnetz',
    'Class:lnkCloudToIPObject/Attribute:source_key' => 'Quellkennung',
    'Class:lnkCloudToIPObject/Attribute:source_key+' => 'Kennung der Quelle, die diese Zuordnung verwaltet. Bei manuellen Zuordnungen bleibt sie leer.',
    'Class:lnkCloudToIPObject/UniquenessRule:no_duplicate' => 'Dieses IP-Objekt ist der Cloud bereits zugeordnet.',
    'Class:lnkCloudToIPObject/UniquenessRule:no_duplicate+' => 'Jeder Block und jedes Subnetz kann einer Cloud nur einmal zugeordnet werden.',
    'Class:CloudNatGatewayLanAddress/Attribute:ipaddress_id' => 'Hostadresse',
    'Class:CloudNatGatewayLanAddress/Attribute:ipaddress_id+' => 'IP-Adresse in der Organisation des Gateways.',

    'Class:CloudNatGatewayPublicAddress/Attribute:ipaddress_id' => 'Hostadresse',
    'Class:CloudNatGatewayPublicAddress/Attribute:ipaddress_id+' => 'IP-Adresse in der Organisation des Gateways.',

    'Class:CloudNetworkLoadBalancerListenerAddress/Attribute:ipaddress_id' => 'Host-Adresse',
    'Class:CloudNetworkLoadBalancerBackendAddress/Attribute:ipaddress_id' => 'Host-Adresse',
]);
