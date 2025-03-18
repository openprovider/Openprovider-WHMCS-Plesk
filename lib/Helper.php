<?php

namespace WHMCS\Module\Server\OpenproviderPleskLicense;

use WHMCS\Database\Capsule;

use WHMCS\Module\Server;


class Helper
{
    public $baseUrl = '';
    public $token = '';
    public $key = '';
    public $method = 'GET';
    public $data = [];
    public $header = [];
    public $endPoint = '';
    public function __construct($params = NULL)
    {

        $this->baseUrl = "https://" . $params['serverhostname'] . "/v1beta/"; 
        $this->token = '';

    }

    /** get the API token */
    public function getPleskApiToken($data=null)
    {
        
        $endPoint = 'auth/login';

        if($data)
        {
            
            $apiData = [
                "username" => $data['serverusername'],
                "password" => $data['serverpassword']
            ];
        }
        else {

            $api_token_details = Capsule::table('tblservers')->where('type','openprovider_plesk_license')->first();
            $apiData = [
                "username" => $api_token_details->username,
                "password" => decrypt($api_token_details->password)
            ];

            $this->baseUrl = "https://" . $api_token_details->hostname . "/v1beta/"; 
            
        }

        $curlResponse = $this->curlCall("POST", $apiData, "TestApiConnection", $endPoint);

        return $curlResponse;
    }


    /** create the custom fields */
    public function createPleskCustomFields($customfieldarray)
    {
            foreach ($customfieldarray as $fieldname => $customfieldarrays) {
 
                if (Capsule::table('tblcustomfields')->where('type', $customfieldarrays['type'])->where('relid', $customfieldarrays['relid'])->where('fieldname', 'like', '%' . $fieldname . '%')->count() == 0) {
                    Capsule::table('tblcustomfields')->insert($customfieldarrays);
                }
            }
    }

    /** Api to get the plesk license items */
    public function getPleskLicenseItems()
    {
        $tokenResponse = $this->getPleskApiToken();
        if($tokenResponse['httpcode'] == 200)
        {
            $this->token = $tokenResponse['result']->data->token;

            /** api to get the license items */
            $endPoint = 'licenses/items/';

            $baseUrl = $this->baseUrl;
            $getUrl =  $baseUrl.$endPoint;
            $curlResponse = $this->curlCall("GET", $getUrl, "getPleskLicenseItems", $endPoint);
            return $curlResponse;
        }
    
        return $tokenResponse;
    }

    /** Get the checked/selected services config options */
    public function getCheckedConfigItemsCheckDrop($sid,$optn,$configOptions)
    {
        global $whmcs;

       
        $selectedOptions = array_filter($configOptions, function ($value) {
            return $value == 1;
        });

        $filteredDropValues = array_values(array_filter($configOptions, function ($value) {
            return $value !== 0 && $value !== "no_option" && $value !== 1;
        }));
        
       
        // Get only the keys (option names)
        $selectedOptionNames = array_keys($selectedOptions);
        
        // Output result
        $filteredItems = [$optn];
      
        if(isset($selectedOptionNames))
        {
            $getLicenseItems = $this->getPleskLicenseItems();

            foreach ($getLicenseItems['result']->data->results as $result) {
                if (in_array($result->title, $selectedOptionNames, true)) {
                    $filteredItems[] = $result->item; // Store only the item values
                }
            }

           
        }
        if(isset($filteredDropValues))
        {
            $filteredItems = array_merge($filteredItems, $filteredDropValues);
        }


        return $filteredItems;
        
    }


    /** Get the checked services config options */
    public function getCheckedConfigItemsNew($sid,$optn,$configOptions)
    {
        global $whmcs;
        $selectedOptions = array_filter($configOptions, function ($value) {
            return $value == 1;
        });
        
        // Get only the keys (option names)
        $selectedOptionNames = array_keys($selectedOptions);
        
        // Output result
        $filteredItems = [$optn];
      
        if(isset($selectedOptionNames))
        {
            $getLicenseItems = $this->getPleskLicenseItems();

            foreach ($getLicenseItems['result']->data->results as $result) {
                if (in_array($result->title, $selectedOptionNames, true)) {
                    $filteredItems[] = $result->item; // Store only the item values
                }
            }

        }
        return $filteredItems;
        
    }
    /** Get the checked services config options from the databse */
    public function getCheckedConfigItems($sid,$optn)
    {
        
        global $whmcs;

        $hostingData = Capsule::table('tblhosting as h')
        ->join('tblhostingconfigoptions as hc', 'h.id', '=', 'hc.relid')
        ->where('h.id', $sid)
        ->where('hc.qty', 1)
        ->select('hc.*') // Select only fields from tblhosting
        ->get();

        if ($hostingData->count() > 0)
        {
            foreach($hostingData as $data)
            {
                $configIds[] = $data->configid;
            }

            $configData = Capsule::table('tblproductconfigoptionssub')->whereIn('configid',$configIds)->get();
            if($configData)
            {
                $optionNames = [$optn];
                foreach ($configData as $result) {
                    $optionParts = explode('|', $result->optionname);
                    if (!empty($optionParts[0])) {
                        $optionNames[] = $optionParts[0] ;
                    }
                }

                // Convert array to comma-separated string
                $formattedString = $optionNames;

            }
            
            return $formattedString;
          
        }else{

            $formattedString = [$optn];
            return $formattedString;
        }
        
    }
    /** Api to create the plesk license */
    public function createPleskLicense($apiData)
    {

        $tokenResponse = $this->getPleskApiToken();
        if($tokenResponse['httpcode'] == 200)
        {
            $this->token = $tokenResponse['result']->data->token;

                     
            $endPoint = 'licenses/plesk/';
            $curlResponse = $this->curlCall("POST", $apiData, "createPleskLicense", $endPoint);
            return $curlResponse;
        }
    
        return $tokenResponse;

    }

     /** Api to Update the plesk license */
    public function updatePleskLicense($apiData,$key)
    {

        $tokenResponse = $this->getPleskApiToken();
        if($tokenResponse['httpcode'] == 200)
        {
            $this->token = $tokenResponse['result']->data->token;
        
            $endPoint = 'licenses/plesk/'.$key;
            $curlResponse = $this->curlCall("PUT", $apiData, "updatePleskLicense", $endPoint);
            return $curlResponse;
        }

        return $tokenResponse;

    }


     /** Api to terminate the plesk license */
    public function deleteLicensePlesk($key)
    {
        $tokenResponse = $this->getPleskApiToken();
       
        if($tokenResponse['httpcode'] == 200)
        {
            $this->token = $tokenResponse['result']->data->token;

            $endPoint = 'licenses/plesk/'.$key;
            $curlResponse = $this->curlCall("DELETE", '', "deletePleskLicense", $endPoint);

            return $curlResponse;
        }
    
        return $tokenResponse;
    }

    /** Api to suspend/unsuspend the plesk license */
    public function suspendUnsuspendPleskLicense($apiData,$key)
    {
        $tokenResponse = $this->getPleskApiToken();
       
        if($tokenResponse['httpcode'] == 200)
        {
            $this->token = $tokenResponse['result']->data->token;

            $endPoint = 'licenses/plesk/'.$key;
            $curlResponse = $this->curlCall("PUT", $apiData, "suspendPleskLicense", $endPoint);
            return $curlResponse;
        }
    
        return $tokenResponse;
    }

    /** change License IP address */
    public function changePleskIpAddress($ip,$key)
    {
        $tokenResponse = $this->getPleskApiToken();
        
        if($tokenResponse['httpcode'] == 200)
        {
            $key = decrypt($key);
            $this->token = $tokenResponse['result']->data->token;

            $apiData = ['ip_address_binding' => $ip];
            $endPoint = 'licenses/plesk/'.$key;
            $curlResponse = $this->curlCall("PUT", $apiData, "changePleskLicenseIP", $endPoint);
            return $curlResponse;
        }
    
        return $tokenResponse;
    }
    /** Update the fields data  */
    public function insert_plesk_custom_fields_value($serviceid, $package_id, $fields = [])
    {
        try {
            foreach ($fields as $key => $value) {
                $custom_field_data = Capsule::table('tblcustomfields')->where("type", "product")->where("fieldname", "like", "%$key%")->where("relid", $package_id)->first();

                if ($custom_field_data) {
                    $field_value = Capsule::table('tblcustomfieldsvalues')->where("fieldid", "=", $custom_field_data->id)->where("relid", "=", $serviceid)->first();
                    
                    if ($field_value->id) {
                        $field_value = Capsule::table('tblcustomfieldsvalues')->where("fieldid", "=", $custom_field_data->id)->where("relid", "=", $serviceid)->update(["value" => $value]);
                    } else {
                        $field_value = Capsule::table('tblcustomfieldsvalues')->insert(["fieldid" => $custom_field_data->id, "relid" => $serviceid, "value" => $value]);
                    }
                }
            }

            return "success";
        } catch (\Exception $e) {
            logActivity('funtion(insert_plesk_custom_fields_value) Openprovider Plesk License Error:', $e->getMessage());
            return $e->getMessage();
        }
    }

    /** functions to create the config gorup and there suboptions */
    public function create_configurable_options($group_name, $pid, $configurableOptions)
    {
        try {

            $configgroup = self::create_config_group($group_name);

            $configLinkId = self::create_config_links($configgroup, $pid);

            foreach ($configurableOptions as $key => $value) {
                if ($value["optiontype"] == "1" || $value["optiontype"] == "2" || $value["optiontype"] == "3") {
                    $configgroupOption = self::config_group_option($configgroup, $value["friendlyName"], $value["optiontype"]);
                } else {
                    $configgroupOption = self::config_group_option($configgroup, $value["friendlyName"], $value["optiontype"], $value["qtyminimum"], $value["qtymaximum"]);
                }
                if (!empty($value["optionvalue"])) {
                    foreach ($value["optionvalue"] as $optiontypekey => $optiontypevalue) {

                        $friendlyName = ($optiontypekey . "|" . $optiontypevalue);
                        self::config_group_sub_option($configgroupOption, $friendlyName);
                    }
                } else {
                    self::config_group_sub_option($configgroupOption, " ");
                }
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    function create_config_group($name, $description = '')
    {
        try {
            $data = [
                'name' => $name,
                'description' => $description
            ];
            $name = explode("|", $name);
            $get_group_id = Capsule::table('tblproductconfiggroups')->where("name", "like", "%" . $name["0"] . "%")->first();

            if (empty($get_group_id->name)) {
                $confid_id = Capsule::table('tblproductconfiggroups')->insertGetId($data);
                return $confid_id;
            } else {
                $confid_id =  $get_group_id->id;
                return $confid_id;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    function create_config_links($gid, $pid, $status = "insert")
    {
        try {
            $data = [
                'gid' => $gid,
                'pid' => $pid
            ];

            $check_exxisting_data = Capsule::table('tblproductconfiglinks')->where("pid", "=", $pid)->where("gid", "=", $gid)->first();
            if (empty($check_exxisting_data)) {
                $inserted_id = Capsule::table('tblproductconfiglinks')->insertGetId($data);
                return $inserted_id;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    function config_group_option($id, $optionname, $optiontype = '1', $qtyminimum = '0', $qtymaximum = '0', $order = '0', $hidden = '0')
    {

        try {
            $data = [
                'gid' =>  $id,
                'optionname' => $optionname,
                'optiontype' => $optiontype,
                'qtyminimum' => $qtyminimum,
                'qtymaximum' => $qtymaximum,
                'order' => $order,
                'hidden' => $hidden

            ];
            $get_group_id = Capsule::table('tblproductconfigoptions')->where('gid', $id)->where('optionname', $optionname)->first();
            if (empty($get_group_id)) {
                $ConfigGroupOption_id = Capsule::table('tblproductconfigoptions')->insertGetId($data);
                return $ConfigGroupOption_id;
            } else {

                $ConfigGroupOption_id =  $get_group_id->id;
                return $ConfigGroupOption_id;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    function config_group_sub_option($configid, $optionname, $sortorder = '0',  $hidden = '0')
    {
        try {
            $data = [
                'configid' =>  $configid,
                'optionname' => $optionname,
                'sortorder' => $sortorder,
                'hidden' => $hidden
            ];
            $get_group_id = Capsule::table('tblproductconfigoptionssub')->where('configid', $configid)->where('optionname', $optionname)->first();
            if (empty($get_group_id)) {
                $ConfigGroupOption_id = Capsule::table('tblproductconfigoptionssub')->insertGetId($data);
                $command = 'GetCurrencies';
                $results = localAPI($command);
                foreach ($results as $key => $val) {
                    if ($key == 'currencies') {
                        foreach ($val as $key1 => $val1) {
                            foreach ($val1 as $key2 => $val2) {
                                $data_cur = [
                                    'type' =>  'configoptions',
                                    'currency' => $val2['id'],
                                    'relid' => $ConfigGroupOption_id,
                                    'msetupfee' => '0.00',
                                    'qsetupfee' => '0.00',
                                    'ssetupfee' => '0.00',
                                    'asetupfee' => '0.00',
                                    'bsetupfee' => '0.00',
                                    'tsetupfee' => '0.00',
                                    'monthly' => '0.00',
                                    'quarterly' => '0.00',
                                    'semiannually' => '0.00',
                                    'annually' => '0.00',
                                    'biennially' => '0.00',
                                    'triennially' => '0.00'
                                ];
                                $ConfigGroupOption_cur_id = Capsule::table('tblpricing')->insertGetId($data_cur);
                            }
                        }
                    }
                }
                return $ConfigGroupOption_id;
            } else {

                $ConfigGroupOption_id =  $get_group_id->id;
                return $ConfigGroupOption_id;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /** End */

    /** Get the plesk license details */
    public function getPleskLicenseDetail($keyid)
    {
        $tokenResponse = $this->getPleskApiToken();
        if($tokenResponse['httpcode'] == 200)
        {
            $this->token = $tokenResponse['result']->data->token;

            $endPoint = 'licenses/plesk/'.$keyid;
            $curlResponse = $this->curlCall("GET", '', "getPleskLicenseDetail", $endPoint);

            return $curlResponse;
        }
    
        return $tokenResponse;

    }

    /** Update Config Groups */
    public function updateConfigGroup($plesk_type,$pid)
    {
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
    }


    /** create the email Template */
    public function create_PleskEmailTemplate()
    {
        try {
           
            if (!Capsule::table('tblemailtemplates')->where('type', 'product')->where('name', 'OpenProvider Plesk License Welcome Email')->count()) {
                Capsule::table('tblemailtemplates')->insert([
                    'type' => 'product',
                    'name' => 'OpenProvider Plesk License Welcome Email',
                    'subject' => 'Server Welcome Email',
                    'message' => '<p>Dear {$client_name},</p><p>Your Plesk license details are provided below:</p>
                                    <p>{$license_details}</p>',
                    'custom' => 1
                ]);
            }
        } catch (\Exception $e) {
            logActivity("Error domainFailData function" . $e->getMessage());
        }
    }
    /** send the email */
    function sendEmail($postData)
    {
        try {
           
        
            $command = 'SendEmail';
            $results = localAPI($command, $postData);
            logModuleCall('plesk_email', $command, $postData, $results);
            return $results;
        } catch (\Exception $e) {
            logActivity("Error SendEmail function" . $e->getMessage());
        }
    }

    function generatePleskLicenseEmailContent($getLicense) {

        $html = '<style>
            ul.license-details li { padding-bottom: 10px; display: flex; justify-content: space-between; }
            ul.license-details { margin-top: 20px; max-width: 530px; text-align: left; padding: 20px; border: 1px solid #ddd; background: #fff; border-radius: 10px; }
            ul.license-details li:nth-child(even) { background: #f4f4f4; }
            .license-extensions table { border: 1px solid #ccc; margin: 20px; width: 100%; border-collapse: collapse; }
            .license-extensions table th, .license-extensions table td { border: 1px solid #ccc; padding: 10px; text-align: center; }
            .license-extensions h2 { font-weight: 600; margin: 20px; }
        </style>';
    
        if (isset($getLicense['result']->data->key_number)) {
            $responseData = [
                'product' => 'Plesk',
                'keyType' => $getLicense['result']->data->key->title,
                'licenseNum' => $getLicense['result']->data->key_number,
                'activation_code' => $getLicense['result']->data->activation_code,
                'ip_address' => $getLicense['result']->data->ip_address_binding,
                'billing_type' => $getLicense['result']->data->billing_type,
                'period' => $getLicense['result']->data->period,
                'order_date' => date('Y-m-d', strtotime($getLicense['result']->data->order_date)),
                'expiry_date' => date('Y-m-d', strtotime($getLicense['result']->data->expiration_date)),
                'status' => (isset($getLicense['result']->data->status) && $getLicense['result']->data->status == 'ACT') ? 'Active' : 'Inactive',
                'features' => $getLicense['result']->data->features ?? [],
                'extensions' => $getLicense['result']->data->extensions ?? []
            ];
    
            $html .= '<ul class="license-details">
                        <li><strong>Product:</strong> Plesk</li>
                        <li><strong>Key Type:</strong> ' . $responseData['keyType'] . '</li>
                        <li><strong>License Number:</strong> ' . $responseData['licenseNum'] . '</li>
                        <li><strong>Activation Code:</strong> ' . $responseData['activation_code'] . '</li>
                        <li><strong>IP Address Binding:</strong> ' . $responseData['ip_address'] . '</li>
                        <li><strong>License Type:</strong> ' . $responseData['billing_type'] . '</li>
                        <li><strong>Period:</strong> ' . $responseData['period'] . ' Month</li>
                        <li><strong>Order Date:</strong> ' . $responseData['order_date'] . '</li>
                        <li><strong>Expiration Date:</strong> ' . $responseData['expiry_date'] . '</li>
                        <li><strong>Status:</strong> ' . $responseData['status'] . '</li>
                    </ul>';
    
            if (!empty($responseData['features'])) {
                $html .= '<ul class="license-featured"><strong>Enabled Features:</strong>';
                foreach ($responseData['features'] as $feature) {
                    $html .= '<li>' . $feature->title . '</li>';
                }
                $html .= '</ul>';
            }
    
            if (!empty($responseData['extensions'])) {
                $html .= '<div class="license-extensions">
                            <h2>Attached Licenses:</h2>
                            <table>
                                <tr>
                                    <th>Key Type</th>
                                    <th>License Number</th>
                                    <th>License Type</th>
                                    <th>Period</th>
                                    <th>Activation Code</th>
                                    <th>Order Date</th>
                                    <th>Expiration Date</th>
                                </tr>';
                foreach ($responseData['extensions'] as $extension) {
                    $html .= '<tr>
                                <td>' . $extension->key->title . '</td>
                                <td>' . $extension->key_number . '</td>
                                <td>' . $extension->billing_type . '</td>
                                <td>' . $extension->period . ' Month</td>
                                <td>' . $extension->activation_code . '</td>
                                <td>' . date('Y-m-d', strtotime($extension->order_date)) . '</td>
                                <td>' . date('Y-m-d', strtotime($extension->expiration_date)) . '</td>
                              </tr>';
                }
                $html .= '</table></div>';
            }
        } else {
            $html .= '<p>No record found</p>';
        }
        
        return $html;
    }

    
     /* Retrieve the Curl API response.*/
    public function curlCall($method, $data = null, $action, $endpoint = null)
    {
         
        $baseUrl = $this->baseUrl;
        $curl = curl_init();
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count((array) $data) > 0 ? json_encode($data) : ""));
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count((array) $data) > 0 ? json_encode($data) : ""));
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count((array) $data) > 0 ? json_encode($data) : ""));
                break;
            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        
       
        curl_setopt($curl, CURLOPT_URL, $baseUrl . $endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10); //timeout in seconds
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        if(isset($this->token) && $this->token != '')
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->token, 'Content-Type: application/json'));
        else
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
       
        $response = curl_exec($curl);

       
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl));
        }
        curl_close($curl);
        $status = ($httpCode == 201 || $httpCode == 200) ? "success" : "failed";

        logModuleCall("Open Plesk Server", $action, $data, json_decode($response));

        return ['httpcode' => $httpCode, 'result' => json_decode($response)];
    }
}