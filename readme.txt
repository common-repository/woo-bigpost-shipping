=== Big Post Shipping for WooCommerce ===
Contributors: fusedsoftware
Tags:bigpost,shipping,freight,australia,post,big post,transport,plugin,calculator,shoppingcart,sendle,parcel,freightbroker,TNT,Fastway,Toll,Northline,courier,couriers,delivery,cartons,pallets 
Requires at least: 4.7
Tested up to: 6.4
Stable tag: 4.7
Requires PHP: 5.2.4
WC requires at least: 3.4.0
WC tested up to: 6.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Use the Big Post Shipping plugin with your WooCommerce website to present quotes and shipping options to visitors to your store.

== Description ==

Big Post's Shipping plugin for WooCommerce brings the convenience of real-time, accurate shipping quotes directly from the Big Post system to your customers. When orders are checked out through your shopping cart with Big Post rates, they are automatically created in the Big Post system for your review and despatch, eliminating the need for manual order entry.

Moreover, our plugin offers an array of benefits to Big Post customers:

*   Get shipping rates for both small and large, heavy, and bulky items, accommodating a wide range of products.
*   Enhance your customers' shopping experience with an optional 'Quick Quote' widget on your product pages.
*   Easily despatch your goods to over 170 Big Post depots nationwide for collection.
*   Provide your customers with B2B, Home Delivery, and Depot Collection rates at checkout and on the product page. We even prompt customers to let us know if they have access to a forklift, enabling us to offer B2B quotes for bulkier items; and provide customers with the option to select ‘Authority to leave’ services for home deliveries.
*   Seamlessly support order syncing for orders placed with flat-rate or free shipping while still ensuring the order is created in the Big Post system.
*   Easily integrate with WooCommerce shipping zones for efficient shipping management.
*   Make use of the product-specific shipping settings and the option to limit shipping methods to specific products.
*   Access great shipping rates from top carriers including TNT, Courier's Please, Northline, Capital Transport, Hi Trans, Hunter Express, Allied Express, Aramex, and more.
*   Maximize your profits by adding a margin to the sale of your freight.
*   For a hassle-free experience, our developers offer installation and customization assistance through our 'Assisted Installation' service, for a fee.

With Big Post's Shipping plugin for WooCommerce, you can provide your customers with seamless and cost-effective shipping solutions, all while streamlining your shipping processes and boosting your business revenue.

For more information on Big Post shipping services, please contact salesteam@bigpost.com.au 


== Installation ==

Below is a quick and simple setup guide for the Big Post plugin:

Note: You must have an existing and active Big Post account in order to use this plugin. To create an account contact salesteam@bigpost.com.au to discuss your business freight needs. 

1. Contact helpdesk@bigpost.com.au or 03 9544 5525 for your unique API token and plugin documents/guides.
2. Install the ‘Big Post Shipping’ plugin from the plugin store (or upload the plugin files to the /wp-content/plugins/woo-bigpost-shipping directory).
3. Activate the plugin from the ‘Plugin’ page in the backend of your website.
4. Navigate to WooCommerce > Settings > Shipping >’Big Post Shipping’ to configure your plugin.
5. Add your plugin key into the ‘Production Plugin Key’ field.
6. Create at least one pickup or warehouse location.
7. At Product level, on each ‘Edit Product’ page, fill the Product Quote Settings Customisation box for each of the products you wish to ship via Big Post and enable the plugin on each product.
8. Add Big Post Shipping as a shipping method in your shipping zone(/s).
9. Use the plugin customisation guide to configure additional plugin settings. 


== Frequently Asked Questions ==

= Where can I get my plugin key? =

Call Big Post on 03 9544 5525 to request the plugin documentation and your unique plugin key. 

= I can't see it on my product pages =

If you can't see the Big Post Shipping widget on your product pages, you've likely missed one of three steps:

1. You haven't added your Big Post plugin key yet
1. You haven't added your first pick up warehouse yet
1. You haven't added the shipping details to your products yet

= What is a testing key? =

This is for select users only

== Screenshots ==

1. Settings
2. Settings
3. Product Page
4. Product Page
5. Checkout Page

== Changelog ==

= 1.0 =
* First major plugin release
= 1.0.1 =
* Changed input type of API Key fields
= 1.0.2 =
* Fixed the condition when showing/hiding Big Post Quote Form based on product dimensions
= 1.0.3 =
* Added default empty message if quotes are empty
= 1.0.4 =
* Updated conditions on product metabox html
= 1.0.5 =
* Fixed data types on product dimensions
= 1.0.6 =
* Added a display the quote form if the api access is in staging mode
= 1.0.7 =
* Added Order Trigger Stage on Settings page and use this settings to trigger pushing of order
* Updated the function for sending order to Big Post
= 1.0.8 =
* Fixed issues on rounding of prices when Round Shipping Price option on Settings is changed
= 1.0.9 =
* Added Postcode reordering on Settings page
= 1.0.10 =
* Fixed issues on variable products not returning correct quotes
= 1.0.11 =
* Fixed bug when adding and saving variable product dimensions
= 1.0.12 =
* Fixed the function for checking non-empty product cartons
= 1.0.13 =
* Added a workaround so that Big Post Shipping will show on the list of available shipping methods and
  can also be sorted,enabled/disabled when adding Shipping Zones
= 1.0.14 =
* Fixed the condition when setting HDS as fallback delivery type
* Updated plugin texts and form labels
* Added rating link on plugin settings page and product quote setting
= 1.0.15 =
* Fixed missing file leave_rating.php
= 1.0.16 =
* Business + No Forklift Bug Fix

= 1.1 =
* Performance enhancement to increase quoting speed
= 1.1.1 =
* Fixed Shipping Type with Use Admin Setting bug
= 1.1.2 =
* Changed API URL to https://api.bigpost.com.au
* Added condition on order processing to prevent sending of request to bigpost if shipping method is not woobigpost
* Added a condition to not show bigpost quote widget and rate on cart and checkout when all items are disabled
* Fixed Bug when a variable has an old existing incomplete simple box data
= 1.1.3 =
* Added a new condition to display the message for Business delivery - no forklift
* Changed the condition from over 40kg to 30kg
* Added rounding to length, width, height during product migration
= 1.1.4 =
* Fixed checkout loading
* Removed dump data
= 1.1.5 =
* Added default value to ATL Description
= 1.1.6 =
* Removed the condition when getting the API URL
* Fixed the issue about sending disabled items to bigpost after placing an order
* Fixed the issue about the showing of bigpost form fields when there's a disabled item/s on cart
* Fixed warnings found
* Unset additional variables set by bigpost on the session after placing an order
= 1.1.7 =
* Fixed shipping Types error after migration
= 1.1.8 =
* Migration hotfix for missing shipping types
= 1.1.9 =
* Migration hotfix for missing authorization option
= 1.1.10 =
* Fixed character encoding issue on Item Description when pushing orders to Big Post

= 1.2 =
* Improved performance fixes
* Added ability to exclude tax
= 1.2.1 =
* Tax settings bug fix
= 1.2.2 =
* Added Cart / Checkout loading message
= 1.2.3 =
* Fixed bug when adding margin
= 1.2.4 =
* Fixed bug after checkout
= 1.2.5 =
* Make 1 as default value to No. of Items field
= 1.2.6 =
* Add handling if product settings for shipping type has empty values
= 1.2.7 =
* Remove hooks to display Big Post details on order email and thank you page
= 1.2.8 =
* Add handling for composite products
= 1.2.8 =
* Add handling for composite products
= 1.2.9 =
* Updated missed file
= 1.2.10 =
* Bug fixes

= 1.3 =
* Fixed found bug on payload
* Fixed js on mobile
= 1.3.1 =
* Fixed not matching shipping fee from widget and total
= 1.3.2 =
* Changed version check function name to avoid conflict
= 1.3.3 =
* Fixed the handling of free shipping on single product page
= 1.3.4 =
* Fixed javascript issues on IE
= 1.3.5 =
* Map company  name on order payload
= 1.3.6 =
* Fixed limitations on suburb list
= 1.3.7 =
* Performance improvements
= 1.3.8 =
* Tested in WP 5.5 and WC 4.4
* Minor bug fixes
= 1.3.9 =
* Quick edit bug fix
= 1.3.10 =
* Server compatibility upgrades
= 1.3.11 =
* Improved plugin quote error handling
= 1.3.12 =
* Cart Quoting bug fix
= 1.3.13 =
* More cart bug fixes
= 1.3.14 =
* Fixed free shipping display on cart/checkout for product variations
= 1.3.15 =
* Fixed error in product and cart page
= 1.3.16 =
* Fixed billing state error in checkout
= 1.3.17 =
* Fixed depot related error in cart and heckout
= 1.3.18 =
* Fixed product widget price tax exclusive
= 1.3.19 =
* Fixed product widget php error
= 1.3.20 =
* Fixed checkout session when subrub doesnt match
= 1.3.21 =
* Added session validation
= 1.3.22 =
* Fixed issue when having multiple warehouse
= 1.3.23 =
* Updated API url for staging
= 1.3.24 =
* Fixed errors showing in checkout

= 1.4 =
* Updated Guzzle Library to 7.3
= 1.4.1 =
* Adding missing files
= 1.4.2 =
* Fixed select2 issue in checkout
= 1.4.3 =
* Fixed rounding issue with tax computation
= 1.4.4 =
* Fixed box migration php error
= 1.4.5 =
* Box Migration validation
= 1.4.6 =
* Added pagination when migrating boxes
= 1.4.7 =
* Added error handling for WC Customer object
= 1.4.8 =
* New approach to WC Customer object fix
= 1.4.9 =
* Fixed when conflicting Guzzle with other plugin

= 1.5 =
* Added ServiceCode to createjob Payload
= 1.5.1 =
* ServiceCode payload fix
= 1.5.2 =
* Added order sync only feature
= 1.5.3 =
* Bug fix when postcode is existing in checkout
= 1.5.4 =
* Fixed php notices and warnings
= 1.5.5 =
* Fixed multiple warehouse payload issue
= 1.5.6 =
* Added loader when selecting suburb in checkout
= 1.5.7 =
* Added carrier restriction feature
= 1.5.8 =
* Default warehouse bugfix
= 1.5.9 =
* Fixed rounding issue with tax exclusive quotes

= 1.6 =
* Updated depot delivery days to 6 days
= 1.6.1 =
* Fixed warning when product is missing cartons
= 1.6.2 =
* Allow 0 dimension when "Present plugin for this product?" is not checked
= 1.6.3 =
* Allow no of items to be 0 when "Present plugin for this product?" is not checked
= 1.6.4 =
* Fixed fatal errors in admin page for some users
= 1.6.5 =
* Added phone number limit settings

= 1.7.1 =
* Improved live quoting in checkout
= 1.7.2 =
* More quoting improvements in checkout
= 1.7.3 =
* Fixed JS error in checkout page
= 1.7.4 =
* Fixed staging endpoint
= 1.7.5 =
* Bug Fixes
* Payload improvements
* Ability to toggle HDS 
= 1.7.6 =
* Bug Fixes for importing products
= 1.7.7 =
* HDS checkbox editable in product page
= 1.7.8 =
* Fixed HDS ATL quote for variables
= 1.7.9 =
* Fixed float error when importing
= 1.7.10 =
* Fixed php errors in checkout
= 1.7.11 =
* Fixed checkout stucking issue
= 1.7.13 =
* Removed consolidated settings
= 1.7.14 =
* Removed consolidated settings
= 1.7.18 =
* Fixed js error in checkout
= 1.7.19 =
* Fixed php errors
= 1.7.20 =
* Fixed order only sync
= 1.7.21 =
* Fixed order only sync
= 1.7.22 =
* Fixed php fatal error in admin product page
= 1.7.23 =
* Fixed php deprecation notice
= 1.7.24 =
* Added sourceType=2 in createJob payload
= 1.7.25 =
* Added Always ATL option
= 1.7.26 =
* Fixed ATL value
= 1.7.27 =
* Fixed php fatal error
= 1.7.28 =
* Added is MHP in payload and carton settings, tested upto WP 6.4

= 1.8 =
* Text changes
= 1.8.1 =
* Updated plugin description
= 1.8.2 =
* Added ETA in checkout widget
= 1.8.3 =
* Updated restricted carriers list
= 1.8.4 =
* Allowed decimal points to cartons dimensions
* New option to hide widget in checkout
* Then show all quote options instead
= 1.8.5 =
* Added display ETA settings
* Added ETA additional days field
= 1.8.6 =
* Fixed less than 1 dimension quoting
* Moved restricted carriers to the bottom in settings page
* Fixed ETA additional days computation
= 1.8.7 =
* Fixed gst discrepancy in checkout widget and total
= 1.8.8 =
* Fixed decimal discrepancy between widget and shipping
= 1.8.9 =
* Fixed checkout NaN issue on GST
= 1.8.10 =
* Fixed product widget bug when disabled
= 1.8.11 =
* Limit item descriptions to 50 chars
= 1.8.12 =
* Fixed product widget quoting

== 2.0 ==
* CIS Integration
= 2.0.1 =
* Fixed error when disabling CIS feature
= 2.0.2 =
* Fixed php error when migrating
= 2.0.3 =
* Fixed variation sku when quoting in checkout from CIS
= 2.0.4 =
* Fixed missing productId when quoting
= 2.0.5 =
* Enabled HPOS compatibility
= 2.0.6 =
* Fixed depot payload on order
= 2.0.7 =
* Fixed billing suburb error in checkout
= 2.0.8 =
* Fixed auth_leave always being false in checkout
= 2.0.9 =
* Fixed default radio selection in widget
= 2.0.10 =
* Fixed Always ATL widget result
