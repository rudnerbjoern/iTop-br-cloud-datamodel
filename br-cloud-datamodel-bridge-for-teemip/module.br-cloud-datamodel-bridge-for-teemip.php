<?php
/** Associate Clouds with TeemIP blocks and subnets when IP Management is selected. */

SetupWebPage::AddModule(__FILE__, 'br-cloud-datamodel-bridge-for-teemip/0.2.0', [
    'label' => 'Cloud integration with TeemIP',
    'category' => 'business',
    'itop_version' => '3.3.0',
    'dependencies' => [
        'br-cloud-datamodel/0.2.0',
        'teemip-ip-mgmt/3.3.1 || !(teemip-ip-mgmt/0.0.0)',
    ],
    'mandatory' => false,
    'visible' => true,
    'auto_select' => 'SetupInfo::ModuleIsSelected("br-cloud-datamodel") && SetupInfo::ModuleIsSelected("teemip-ip-mgmt")',
    'datamodel' => [],
    'webservice' => [],
    'dictionary' => [],
    'data.struct' => [],
    'data.sample' => [],
    'doc.manual_setup' => '',
    'doc.more_information' => '',
    'settings' => [],
]);
