<?php
/** French labels for Cloud network inventory links to TeemIP objects. */

Dict::Add('FR FR', 'French', 'Français', [
    'Class:Cloud/Attribute:ip_objects_list' => 'Blocs IP et sous-réseaux',
    'Class:Cloud/Attribute:ip_objects_list+' => 'Blocs et sous-réseaux TeemIP associés à ce nuage.',
    'Class:lnkCloudToIPObject' => 'Lien entre un nuage et un objet IP',
    'Class:lnkCloudToIPObject+' => 'Association entre un nuage et un bloc ou un sous-réseau TeemIP.',
    'Class:lnkCloudToIPObject/Attribute:cloud_id' => 'Nuage',
    'Class:lnkCloudToIPObject/Attribute:ipobject_id' => 'Bloc IP ou sous-réseau',
    'Class:lnkCloudToIPObject/Attribute:source_key' => 'Identifiant de la source',
    'Class:lnkCloudToIPObject/Attribute:source_key+' => 'Identifiant de la source qui gère cette association. Vide pour les liens créés manuellement.',
    'Class:lnkCloudToIPObject/UniquenessRule:no_duplicate' => 'Cet objet IP est déjà associé à ce nuage.',
    'Class:lnkCloudToIPObject/UniquenessRule:no_duplicate+' => 'Chaque bloc ou sous-réseau ne peut être associé qu’une seule fois à un même nuage.',
    'Class:CloudNatGatewayLanAddress/Attribute:ipaddress_id' => 'Adresse de l’hôte',
    'Class:CloudNatGatewayLanAddress/Attribute:ipaddress_id+' => 'Adresse IP de l’organisation propriétaire de cette passerelle.',

    'Class:CloudNatGatewayPublicAddress/Attribute:ipaddress_id' => 'Adresse de l’hôte',
    'Class:CloudNatGatewayPublicAddress/Attribute:ipaddress_id+' => 'Adresse IP de l’organisation propriétaire de cette passerelle.',
]);
