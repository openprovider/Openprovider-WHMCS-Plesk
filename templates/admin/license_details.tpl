{assign var=unique_id value=10|mt_rand:20}
<link href="{$assets}/css/style.css?v={$unique_id}" rel="stylesheet">

{if $licenseDetails.licenseNum}
    <ul class="license-details">
        <li><strong>{$LANG.product_heading}:</strong> Plesk</li>
        <li><strong>{$LANG.key_type}:</strong> {$licenseDetails.keyType}</li>
        <li><strong>{$LANG.license_number}:</strong> {$licenseDetails.licenseNum}</li>
        <li><strong>{$LANG.activation_code}:</strong> {$licenseDetails.activation_code}</li>
        <li><strong>{$LANG.ip_address}:</strong> {$licenseDetails.ip_address}</li>
        <li><strong>{$LANG.license_type}:</strong> {$licenseDetails.billing_type}</li>
        <li><strong>{$LANG.period}:</strong> {$licenseDetails.period} Month</li>
        <li><strong>{$LANG.order_date}:</strong> {$licenseDetails.order_date}</li>
        <li><strong>{$LANG.expiry_date}:</strong> {$licenseDetails.expiry_date}</li>
        <li><strong>{$LANG.status}:</strong> {$licenseDetails.status}</li>
    </ul>

    {if $licenseDetails.features|@count > 0}
        <ul class="license-featured">
            <span><strong>{$LANG.features_heading}</strong></span>
            {foreach from=$licenseDetails.features item=feature}
                <li>{$feature.title}</li>
            {/foreach}
        </ul>
    {/if}

    {if $licenseDetails.extensions|@count > 0}
        <div class="license-extensions">
            <h2>{$LANG.attach_license}:</h2>
            <table>
                <tr>
                    <th>{$LANG.key_type}</th>
                    <th>{$LANG.license_number}</th>
                    <th>{$LANG.license_type}</th>
                    <th>{$LANG.period}</th>
                    <th>{$LANG.activation_code}</th>
                    <th>{$LANG.order_date}</th>
                    <th>{$LANG.expiry_date}</th>
                </tr>
                {foreach from=$licenseDetails.extensions item=extension}
                    <tr>
                        <td>{$extension.key->title|default:"-"}</td>
                        <td>{$extension.key_number}</td>
                        <td>{$extension.billing_type}</td>
                        <td>{$extension.period} Month</td>
                        <td>{$extension.activation_code}</td>
                        <td>{$extension.order_date|date_format:"%Y-%m-%d"}</td>
                        <td>{$extension.expiration_date|date_format:"%Y-%m-%d"}</td>
                    </tr>
                {/foreach}
            </table>
        </div>
    {/if}
{else}
    <p>No record found</p>
{/if}