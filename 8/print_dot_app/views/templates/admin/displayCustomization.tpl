{*
* 2024 Print.App
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Print.App to newer
* versions in the future. If you wish to customize Print.App for your
* needs please refer to http://Print.App for more information.
*
*  @author    Print.App Inc <hello@Print.App.com>
*  @copyright 2023 Print.App Inc
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Print.App Inc.
*}

<div class="panel" id="orderProductsPanel">
  <div class="card-header">
    <h3 class="card-header-title">
      PrintApp Customizations (<span>{count($products)}</span>)
    </h3>
  </div>

  <div class="card-body">
    <div class="spinner-order-products-container" id="orderProductsLoading">
      <div class="spinner spinner-primary"></div>
    </div>
    
    <table class="table" >
      <thead>
      <tr>
        <th>
          <p class="mb-0">Product</p>
        </th>
        <th col>
          <p class="mb-0">Preview</p>
        </th>
        <th>
          <p class="mb-0">Links</p>
        </th>
      </tr>
      </thead>
      <tbody>
        {foreach from=$products item=product}
            {if $product.print_dot_app_customization }
              <tr class="order-product-customization">
                <td class="border-top-0">{$product.product_name}</td>
                <td>
                    {foreach from=$pp_customization.previews key=k item=v}
                        <a class="ppc-ps-img"><img src="{$v}" class="pp-90thumb" width="120px"></a>
                    {/foreach}
                </td>
                <td  class="border-top-0 text-muted">
                   <div><a target="_blank" href="{$product.print_dot_app_customization.links.pdf}">Download PDF</div>
                   <div><a target="_blank" href="{$product.print_dot_app_customization.links.png}">Download PNG</div>
                   <div><a target="_blank" href="{$product.print_dot_app_customization.links.jpg}">Download JPEG</div>
                   <div><a target="_blank" href="{$product.print_dot_app_customization.links.tiff}">Download TIFF</div>
                </td>
              </tr>
            {/if}
        {/foreach}
      </tbody>
    </table>
  </div>
</div>


