<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

$_addon = array(
    'name'=>'PaidListings',
    'description'=>'Enables charging users for listing submissions.',
    'version'=>'2.4.3.1',
    'min_app_version_required'=>'2.4.9.0',
    'type'=>'Commercial',
    'is_beta'=>0
);

echo json_encode($_addon);