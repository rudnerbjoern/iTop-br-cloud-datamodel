<?php
/** Register the provider-neutral Cloud inventory module for the native iTop model. */

SetupWebPage::AddModule(__FILE__, 'br-cloud-datamodel/0.2.0', [
    'label' => 'Cloud data model',
    'category' => 'business',
    'itop_version' => '3.3.0',
    'dependencies' => [
        'itop-virtualization-mgmt/3.3.0',
        'itop-container-mgmt/3.3.0',
    ],
    'mandatory' => false,
    'visible' => true,
    'datamodel' => [],
    'webservice' => [],
    'data.struct' => [],
    'data.sample' => [],
    'doc.manual_setup' => '',
    'doc.more_information' => '',
    'settings' => [],
]);
