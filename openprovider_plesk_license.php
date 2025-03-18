<?php

use WHMCS\Module\Server\OpenproviderPleskLicense\Helper;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function openprovider_plesk_license_MetaData()
{
    return array(
        'DisplayName' => 'OpenProvider Plesk License',
        'APIVersion' => '1.0', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
    );
}

function openprovider_plesk_license_ConfigOptions(array $params)
{   

    global $whmcs;
    $helper = new Helper();
    $pid = $whmcs->get_req_var("id");
    $product_data = Capsule::table('tblproducts')->select()->where('id', $pid)->first();
    $plesk_type = $product_data->configoption1;


    $customfieldarray = [
        'key_id' =>
        [
            'type' => 'product',
            'fieldname' => 'key_id|Key Id',
            'relid' => $pid,
            'fieldtype' => 'text',
            'description' => '',
            'adminonly' => 'on',
            'sortorder' => '1',
        ],
        'ip_address' => [
            'type' => 'product',
            'fieldname' => 'ip_address|IP Address',
            'relid' => $pid,
            'fieldtype' => 'text',
            'description' => 'Enter the IP address associated with the license',
            'required' => 'on', // Makes it a required field
            'showorder' => 'on', // Shows the field on the order form
            'sortorder' => '2',
        ],
        'comments' => [
            'type' => 'product',
            'fieldname' => 'comments|Comments',
            'relid' => $pid,
            'fieldtype' => 'textarea',
            'description' => 'Enter the comments',
            'showorder' => 'on', // Shows the field on the order form
            'sortorder' => '3',
        ],
        'title' => [
            'type' => 'product',
            'fieldname' => 'title|Title',
            'relid' => $pid,
            'fieldtype' => 'text',
            'description' => 'Enter the title',
            'showorder' => 'on', // Shows the field on the order form
            'sortorder' => '4',
        ],
    ];

    /** create the custom fields */
    $helper->createPleskCustomFields($customfieldarray);


    /** Get the API to fetch the license items */
    $getLicenseItems = $helper->getPleskLicenseItems();


   
    if($getLicenseItems['httpcode'] == 200)
    {
     
        $productFile = __DIR__ . '/plesk-products.json';
      
        $getJsondata  = file_get_contents($productFile);
        $getJsondata = json_decode($getJsondata,true);
        $getPleskItems = array_keys($getJsondata['plesk_products']);



        foreach ($getLicenseItems['result']->data->results  as $key => $result) {

            if (in_array($result->title, $getPleskItems)) {
                
                $options[$result->item] = $result->title;

                /** Code to create the config groups and there options/suboptions */

                $group_name = "{$result->item}|{$result->title}";
                
                foreach ($getJsondata['plesk_products'][$result->title] as $category => $productOptions) {
                
                    // Generate a clean group key
                    $groupKey = strtolower(str_replace([' ', '&', '/'], '_', $category));
                    $groupKey = preg_replace('/[^a-z0-9_]/', '', $groupKey);
    
                    // Match product options with API data (store key => value)
                    $filteredProductData = array_reduce($getLicenseItems['result']->data->results, function ($resultdatac, $result) use ($productOptions) {
                        if (in_array($result->title, $productOptions, true)) {
                            $key = $result->item;
                            $resultdatac[$key] = $result->title; // Store item as key and title as value
                        }
                        return $resultdatac;
                    }, []);
    
                    // Check if there are multiple or single options
                    if (!empty($filteredProductData)) {
                        if (count($filteredProductData) > 1) {
                            // Multiple options: Create dropdown
                            $optionType = "1"; // Dropdown in WHMCS
                            $filteredProductData = ["no_option" => "No Option"] + $filteredProductData;
                            $friendlyName = "$category Options";
                        } else {

                            // Single option: Create checkbox
                            $optionType = "3"; // Checkbox in WHMCS

                            foreach ($filteredProductData as $key => $value) {
                                $filteredProductData[$key] = "$value | $category";
                            }
                            $friendlyName = reset($filteredProductData); // Get the first value 
                        }
    
                        // Create Configurable Option Array
                        $configurableOptions = [
                            [
                                "name" => "$category",
                                "friendlyName" => $friendlyName,
                                "description" => "",
                                "optiontype" => $optionType,
                                "optionvalue" => $filteredProductData // Key => Value pairs
                            ]
                        ];
                        
                        // Call WHMCS function to create the configurable options
                        $response = $helper->create_configurable_options($group_name, $pid, $configurableOptions);
                    }
                }
                /** End  */
            }

        }
        if($plesk_type != '')
        {
            $helper->updateConfigGroup($plesk_type,$pid);

        }
    }
    
    return array(

        'product_items' => array(
            'FriendlyName' => 'Product Items',
            'Type' => 'dropdown',
            'Size' => '25',
            'Options' => $options,
            'Description' => '',
        ),
        'ip_binding' => array(
            'FriendlyName' => 'IP Binding',
            'Type' => 'yesno',
            'Description' => 'Tick to enable',
            'Size' => '25',
        ),
        'installation_command' => array(
            'FriendlyName' => 'Installation Command',
            'Type' => 'textarea',
            'Description' => 'Please enter the each command to the next line',
            'Rows' => '8',
            'Cols' => '60'
        ),
        
       
    );

   
}


/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 */
function openprovider_plesk_license_TestConnection(array $params)
{
    try {

        $helper = new Helper($params);
        $response = $helper->getPleskApiToken($params);
        if($response['httpcode'] == 200){
         
            $success = true;
        }
        else{
            $errorMsg = $response['result']->message;
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}


function openprovider_plesk_license_CreateAccount(array $params)
{

    try {

        global $whmcs;
        $helper = new Helper();


        /** Api to create the plesk license */
        $helper->create_PleskEmailTemplate();

        $serviceId = $params['serviceid'];
       $pid = $params['pid'];
        $keyId = $params['customfields']['key_id'];
        
        $configOptionSelect = $params['configoption1'];

        $configOptions =  $params['configoptions'];
        // print_r($configOptions); 

        $getconfigItms = $helper->getCheckedConfigItemsCheckDrop($serviceId,$configOptionSelect,$configOptions);
      

        $itemsData = [];
        $atttachData = [];

        foreach ($getconfigItms as $item) {
            
            if (strpos($item, 'ADD-') === 0 || strpos($item, 'CLNX-') === 0 || strpos($item, 'SOPHOS-') === 0)  {
                $atttachData[] = $item;
            } else {
                $itemsData[] = $item;
            }
        }

       
        $postData = [
            'attached_keys' => $atttachData,
            'comment' =>  $params['customfields']['comments'] ?? '',
            "ip_address_binding" => $params['customfields']['ip_address'],
            "items" =>  $itemsData, 
            "period" => 1, 
            "restrict_ip_binding" => !empty($params['configoption2']) ? true : false,
            'title' => $params['customfields']['title'] ?? ''
        ];


        if(isset($keyId) && $keyId != '')
        {
           
            $emailKeyId = $keyId;
            $postData['keyId'] = $keyId;
            $getResponse = $helper->updatePleskLicense($postData,$keyId);
            
            if($getResponse['httpcode'] == 200)
            {
            }else{
                return $getResponse['result']->desc;
            }

        }else{
            
            $getResponse = $helper->createPleskLicense($postData);
            
         

            if($getResponse['httpcode'] == 200)
            {
                        
                if (isset($getResponse['result']->data->key_id)) {
                    $keyId = $getResponse['result']->data->key_id;
                    
                    $fields = ["key_id" => $keyId];
                    $emailKeyId = $keyId;
                    $helper->insert_plesk_custom_fields_value($serviceId, $pid, $fields);
                }
                
            }else{
                return $getResponse['result']->desc;
            }
        }

        /** create the HTML email  */
        $getLicense = $helper->getPleskLicenseDetail($emailKeyId);  
        $htmlContent = $helper->generatePleskLicenseEmailContent($getLicense);
        $emailData = [
            'messagename' => 'OpenProvider Plesk License Welcome Email',
            'id' => $serviceId,
            'customvars' => base64_encode(serialize(["license_details" => $htmlContent]))
        ];
        $results = $helper->sendEmail($emailData);
        

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
    return 'success';
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 */
function openprovider_plesk_license_SuspendAccount(array $params)
{
    try {
        $serviceId = $params['serviceid'];
        $pid = $params['pid'];
        $keyId = $params['customfields']['key_id'];
      
        $postData = [
            "ip_address_binding" => $params['customfields']['ip_address'],
            'suspended' => true
        ];

        $helper = new Helper();
        $getResponse = $helper->suspendUnsuspendPleskLicense($postData,$keyId);
        if($getResponse['httpcode'] == 200)
        {
            return 'success';

        }else{
            return $getResponse['result']->desc;
        }
        
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 */
function openprovider_plesk_license_UnsuspendAccount(array $params)
{
    try {

        $serviceId = $params['serviceid'];
        $pid = $params['pid'];
        $keyId = $params['customfields']['key_id'];
      
        $postData = [
            "ip_address_binding" => $params['customfields']['ip_address'],
            'suspended' => false
        ];

        $helper = new Helper();
        $getResponse = $helper->suspendUnsuspendPleskLicense($postData,$keyId);
        if($getResponse['httpcode'] == 200)
        {
            return 'success';

        }else{
            return $getResponse['result']->desc;
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 */
function openprovider_plesk_license_TerminateAccount(array $params)
{
    try {

        $serviceId = $params['serviceid'];
        $pid = $params['pid'];
        $keyId = $params['customfields']['key_id'];

        $helper = new Helper();
        $getResponse = $helper->deleteLicensePlesk($keyId);
       
        if ($getResponse["httpcode"] == 200)
        {
            $fields = ["key_id" => ""];
            $helper->insert_plesk_custom_fields_value($serviceId, $pid, $fields);
            return "success";
        }else {
            return $getResponse['result']->desc;
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.

 */
function openprovider_plesk_license_ChangePackage(array $params)
{
    try {
        
        global $whmcs;
        $helper = new Helper();

        /** Api to update the plesk license */

        $serviceId = $params['serviceid'];
       $pid = $params['pid'];
        $keyId = $params['customfields']['key_id'];
        
        $configOptionSelect = $params['configoption1'];

        $configOptions =  $params['configoptions'];

        $getconfigItms = $helper->getCheckedConfigItemsCheckDrop($serviceId,$configOptionSelect,$configOptions);
       
        $itemsData = [];
        $atttachData = [];

        foreach ($getconfigItms as $item) {
            
            if (strpos($item, 'ADD-') === 0 || strpos($item, 'CLNX-') === 0 || strpos($item, 'SOPHOS-') === 0)  {
                $atttachData[] = $item;
            } else {
                $itemsData[] = $item;
            }
        }

        $postData = [
            'attached_keys' => $atttachData,
            'comment' =>  $params['customfields']['comments'] ?? '',
            "ip_address_binding" => $params['customfields']['ip_address'],
            "items" =>  $itemsData, 
            "period" => 1, 
            "restrict_ip_binding" => !empty($params['configoption2']) ? true : false,
            'title' => $params['customfields']['title'] ?? ''
        ];



        if(isset($keyId) && $keyId != '')
        {
           
            $emailKeyId = $keyId;
            $postData['keyId'] = $keyId;
            $getResponse = $helper->updatePleskLicense($postData,$keyId);
            
            if($getResponse['httpcode'] == 200)
				return 'success';
            else
                return $getResponse['result']->desc;
        }else {
            return 'Keyid Does not exist';
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}



/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.

 */
function openprovider_plesk_license_AdminServicesTabFields(array $params)
{
    try {

        global $CONFIG;
        global $whmcs;
        $helper = new Helper();

        $assets = $CONFIG['SystemURL'] . "/modules/servers/openprovider_plesk_license/assets";
        $language = $CONFIG['Language'];
        $langfilename = __DIR__ . '/lang/' . $language . '.php';
        if (file_exists($langfilename)) {
            require($langfilename);
        } else {
            require(__DIR__ . '/lang/english.php');
        }
        
        $key = $params['customfields']['key_id'];

        $getLicense = $helper->getPleskLicenseDetail($key);      
        
        // Convert stdClass object to an array
        $licenseData = json_decode(json_encode($getLicense['result']->data), true);

        // Prepare license details safely
        $licenseDetails = [];
        if (!empty($licenseData['key_number'])) {
            $licenseDetails = [
                'product'         => 'plesk',
                'keyType'         => $licenseData['key']['title'] ?? '',
                'licenseNum'      => $licenseData['key_number'] ?? '',
                'activation_code' => $licenseData['activation_code'] ?? '',
                'ip_address'      => $licenseData['ip_address_binding'] ?? '',
                'billing_type'    => $licenseData['billing_type'] ?? '',
                'period'          => $licenseData['period'] ?? '',
                'order_date'      => !empty($licenseData['order_date']) ? date('Y-m-d', strtotime($licenseData['order_date'])) : '',
                'expiry_date'     => !empty($licenseData['expiration_date']) ? date('Y-m-d', strtotime($licenseData['expiration_date'])) : '',
                'status' =>     (isset($licenseData['status']) && $licenseData['status'] == 'ACT') ? 'Active' : 'Inactive',
                'features'        => $licenseData['features'] ?? [],
                'extensions'      => $licenseData['extensions'] ?? []
            ];
        }

        // Initialize Smarty
        $smarty = new \Smarty();
        $smarty->assign('licenseDetails', $licenseDetails);
        $smarty->assign('LANG', $_ADDONLANG);
        $smarty->assign('assets', $assets);

        // Render the TPL template
        $licenseDetailsHtml = $smarty->fetch(__DIR__ . '/templates/admin/license_details.tpl');


        return [
            'Plesk License Details' => $licenseDetailsHtml
        ];
        
       
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, simply return no additional fields to display.
    }

    return array();
}


/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 
 */
function openprovider_plesk_license_ClientArea(array $params)
{
    
    try {
        
        global $CONFIG;
        global $whmcs;
        $helper = new Helper();
        
   
        if ($whmcs->get_req_var('customaction')){
            
            if (file_exists(__DIR__ . '/lib/ajax.php'))
                include_once __DIR__ . '/lib/ajax.php';
            exit();
        }

        $assets = $CONFIG['SystemURL'] . "/modules/servers/openprovider_plesk_license/assets";

        $language = $CONFIG['Language'];
        $langfilename = __DIR__ . '/lang/' . $language . '.php';
        if (file_exists($langfilename)) {
            require($langfilename);
        } else {
            require(__DIR__ . '/lang/english.php');
        }

        $key = $params['customfields']['key_id'];

        $commands = explode('<br />', nl2br($params['configoption3']));

        $getLicense = $helper->getPleskLicenseDetail($key);

        // echo '<pre>';
        // print_r($getLicense); die;
        if($getLicense)
        {
           
            $responseData  = [
                'product' => 'plesk',
                'title' => $getLicense['result']->data->title,
                'comment' => $getLicense['result']->data->comment,
                'keyType' =>  $getLicense['result']->data->key->title,
                'keyid' => $getLicense['result']->data->key_id,
                'licenseNum' => $getLicense['result']->data->key_number,
                'activation_code' => $getLicense['result']->data->activation_code,
                'ip_address' => $getLicense['result']->data->ip_address_binding,
                'billing_type' => $getLicense['result']->data->billing_type,
                'period' => $getLicense['result']->data->period,
                'order_date' => date('Y-m-d',strtotime($getLicense['result']->data->order_date)),
                'expiry_date' => date('Y-m-d',strtotime($getLicense['result']->data->expiration_date)),
                'features' => $getLicense['result']->data->features ?? [],
                'extensions' => $getLicense['result']->data->extensions ?? [],
                'status' => (isset($getLicense['result']->data->status) && $getLicense['result']->data->status == 'ACT') ? 'Active' : 'Inactive',
                'keyId' =>$key,
                'command'=>$commands

            ];
        }

   
        $templateFile = 'templates/manage.tpl';
        return array(
            'templatefile' => $templateFile,
            'templateVariables' => array(
                'responseData' => $responseData,
                'assets' => $assets,
                'LANG' => $_ADDONLANG
            ),
        );
        

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_plesk_license',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}