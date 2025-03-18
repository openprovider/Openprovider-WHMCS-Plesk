<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('AdminProductConfigFieldsSave', 1, function($vars) {
    
    global $whmcs;

    $pid = $vars['pid'];
    $product_data = Capsule::table('tblproducts')->select()->where('id', $pid)->first();
    $plesk_type = $product_data->configoption1;
    if($plesk_type != '')
    {
       
        $group_data = Capsule::table('tblproductconfiggroups')->select()->where("name", "like", "%" . $plesk_type . "%")->first();
        $gid = $group_data->id;
        $data = [
            'gid' => $gid,
            'pid' => $pid
        ];

        $check_exxisting_data = Capsule::table('tblproductconfiglinks')->where("pid", "=", $pid)->where("gid", "=", $gid)->first();

        if (empty($check_exxisting_data)) {
            $inserted_id = Capsule::table('tblproductconfiglinks')->insertGetId($data);
        }

        $inserted_id = Capsule::table('tblproductconfiglinks')->where("pid", "=", $pid)->where("gid", "!=", $gid)->delete();
        return $inserted_id;
        
    }
    
});

