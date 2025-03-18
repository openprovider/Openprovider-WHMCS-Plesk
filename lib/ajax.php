<?php
include_once dirname(__FILE__).'/../../../init.php';

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Server\OpenproviderPleskLicense\Helper;
use WHMCS\Database\Capsule;


if ($whmcs->get_req_var('customaction') == "change_ip_address") {


    $ip_address = $whmcs->get_req_var('ip_address');
    $keyId = $whmcs->get_req_var('key_id');
    $helper = new Helper();
    $ipResponse = $helper->changePleskIpAddress($ip_address,$keyId);
    if($ipResponse['httpcode'] == 200){
        
        $serviceId = $params['serviceid'];
        $pid = $params['pid'];
        $fields = ["ip_address" => $ip_address];
        $helper->insert_plesk_custom_fields_value($serviceId, $pid, $fields); 
        echo json_encode(["status" => true, "data" => $ipResponse['result']]);
        exit();
    }else{
        echo json_encode(["status" => false, "data" => $ipResponse['result']]);
        exit();
    }
}
