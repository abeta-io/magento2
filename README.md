# Abeta for Magento® 2

The official Magento 2 plugin for Abeta.
Offer OCI and cXML PunchOut quickly and easily with Abeta. Connect with procurement systems / ERPs such as Coupa, Oracle and Sap Ariba. 
Increase the turnover of existing customers or acquire new customers with the help of B2B connections.

## Installation

#### Magento® Marketplace

This extension will be available on the Marketplace soon.

#### Install via Composer

1. Go to Magento® 2 root folder

2. Enter the following commands to install module:

   ```
   composer require abeta-io/magento2
   ``` 

3. Enter following commands to enable module:

   ```
   php bin/magento module:enable Abeta_PunchOut
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. If Magento® is running in production mode, deploy static content with the following command: 

   ```
   php bin/magento setup:static-content:deploy
   ```

#### Install from GitHub

1. Download the zip package by clicking "Clone or Download" and select "Download ZIP" from the dropdown.

2. Create an app/code/Abeta/PunchOut directory in your Magento® 2 root folder.

3. Extract the contents from the "magento2-abeta" zip and copy or upload everything to app/code/Abeta/PunchOut

4. Run the following commands from the Magento® 2 root folder to install and enable the module:

   ```
   php bin/magento module:enable Abeta_PunchOut
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

5. If Magento® is running in production mode, deploy static content with the following command: 

   ```
   php bin/magento setup:static-content:deploy
   ```