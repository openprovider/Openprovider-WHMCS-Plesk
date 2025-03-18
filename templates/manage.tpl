{assign var=unique_id value=10|mt_rand:20}
<link href="{$assets}/css/style.css?v={$unique_id}" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/validator/13.7.0/validator.min.js"></script>

<script src="{$assets}/js/custom.js?v={$unique_id}"></script>

{if isset($responseData.licenseNum) && $responseData.licenseNum}
    <div class="client-plsk-license-details">
        <div class="header">
            <div class="header-inner">
                <span>{$LANG["key_id"]}: <span id="keyId">{$responseData['keyid']}</span>
                    <button class="copy-btn copyButton" onclick="copyTokeyId()">
                        <i class="fas fa-copy"></i>
                    </button>
                    <!-- <span class="copy-message" style="display: none;">Copied!</span> -->
                </span>

                <span>{$LANG["status"]}: <span class="active-key">{$responseData['status']}</span></span>
            </div>
        </div>
        <div class="card" style="width:100%">
            <div class="card-body">
                <h5 class="card-title">{$responseData['title']}</h5>
                <p class="card-text">{$responseData['comment']}</p>
                <ul class="plsk-license-details">
                    <li><span>{$LANG["license_type"]}</span>
                        <p>{$responseData['billing_type']}</p>
                    </li>
                    <li><span>{$LANG["period"]}</span>
                        <p>{$responseData['period']} Month</p>
                    </li>
                    <li><span>{$LANG["order_date"]}</span>
                        <p>{$responseData['order_date']}</p>
                    </li>
                    <li><span>{$LANG["expiry_date"]}</span>
                        <p>{$responseData['expiry_date']}</p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="client-plsk-license-inner">
            <h3>{$LANG["plesk_heading"]}</h3>
            <table class="table table-bordered">
               
                <tbody>
                    <tr>
                        <th scope="col">{$LANG["product_heading"]}</th>
                        <th>Plesk</th>
                    </tr>
                    <tr>
                        <th scope="col">{$LANG["key_type"]}</th>
                        <td>{$responseData['keyType']}</td>
                    </tr>
                    <tr>
                        <th scope="col">{$LANG["license_number"]}</th>
                        <td>{$responseData['licenseNum']}</td>
                    </tr>
                    <tr>
                        <th scope="col">{$LANG["activation_code"]}</th>
                        <td>{$responseData['activation_code']}</td>
                    </tr>
                    <tr>
                        <th scope="col">{$LANG["ip_address"]}</th>
                        <td>
                            {$responseData['ip_address']}
                            <button class="btn btn-primary ip-modal-btn" id="ip_modalbtn" data-toggle="modal" data-target="#ipModal" data-ip="{$responseData['ip_address']}">
                                {$LANG["change_ip"]}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {if isset($responseData.features) && count($responseData.features) > 0}
            <div class="license-enable-features">
                <h3>{$LANG["features_heading"]}</h3>
                <ul>
                    {foreach from=$responseData['features'] key=key item=value}
                        <li>{$value->title}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}

        {if isset($responseData.extensions) && count($responseData.extensions) > 0}
            <div class="license-extensions">
                <h3>{$LANG["attach_license"]}</h3>
                {foreach from=$responseData.extensions item=value name=extLoop}
                    <div class="card" style="width:100%">
                        <div class="card-body">
                            <h5 class="card-title">{$value->key->title}</h5>
                            <ul class="plsk-license-details">
                                <li><span>{$LANG["license_number"]}</span>
                                    <p>{$value->key_number}</p>
                                </li>
                                <li><span>{$LANG["license_type"]}</span>
                                    <p>{$value->billing_type}</p>
                                </li>
                                <li><span>{$LANG["period"]}</span>
                                    <p>{$value->period} Month</p>
                                </li>
                                <li><span>{$LANG["expiry_date"]}</span>
                                    <p>{$value->expiration_date|date_format:"%Y-%m-%d"}</p>
                                </li>
                            </ul>
                            
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary toggle-btn" data-target="extension-content-{$smarty.foreach.extLoop.index}">
                                    View More
                                </button>
                            </div>

                            <div class="client-plsk-license-inner d-none" id="extension-content-{$smarty.foreach.extLoop.index}">
                                <h3></h3>
                                <table class="table table-bordered">
                                   
                                    <tbody>
                                        <tr>
                                            <th scope="col">{$LANG["product_heading"]}</th>
                                            <td>Plesk</td>
                                        </tr>
                                        <tr>
                                            <th scope="col">{$LANG["key_id"]}</th>
                                            <td>{$value->key_id}</td>
                                        </tr>

                                        <tr>
                                            <th scope="col">{$LANG["status"]}</th>
                                            {if $value->status == "ACT"}
                                                <td><span class="active-key"></span>Active</td>
                                            {else}
                                                <td>Inactive</td>
                                            {/if}
                                        </tr>

                                        <tr>
                                            <th scope="col">{$LANG["activation_code"]}</th>
                                            <td>{$value->activation_code}</td>
                                        </tr>
                                        <tr>
                                            <th scope="col">{$LANG["order_date"]}</th>
                                            <td>{$value->order_date|date_format:"%Y-%m-%d"}</td>
                                        </tr>
                                       
                                    </tbody>
                                </table>
                            </div>

                            
                        </div>
                    </div>
                {/foreach}
            </div>
        {/if}

        {if isset($responseData.command) && $responseData.command}
            {if is_array($responseData.command)}
                {assign var="commands" value=$responseData.command}
            {else}
                {assign var="commands" value="<br />"|explode:$responseData.command}
            {/if}

            <div class="install-cmd-sec">
                <div class="card" style="width:100%">
                    <div class="card-body">
                        <h5 class="card-title">Installation</h5>
                        {foreach from=$commands item=cmd}
                            <span>{$cmd}
                                <button class="copy-btn" onclick="copyCommand(this)">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </span>
                        {/foreach}
                    </div>        
                </div>
            </div>
        {/if}

    </div>
{else}
    <p>No records found.</p>
{/if}


<div class="modal fade" id="ipModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"> {$LANG["bind_ip"]} (<span id="modalIpDisplay"></span>)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="text" id="ipAddress" name="ipAddress" class="form-control" placeholder="Enter IP address">
                    <input type="hidden" id="keyIdMod" name="keyId" class="form-control" placeholder="" value="{encrypt($responseData['keyId'])}">
                    <div id="error-msg" class="invalid-feedback d-block"></div>
                    <div class="alert alert-danger" role="alert"> 
                        {$LANG["bind_ip_msg"]}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success save-ip">Submit</button>
                    <!-- <a class="btn btn-success save-ip">Submit</a> -->
                </div>
            </div>
        </form>
    </div>
</div>

