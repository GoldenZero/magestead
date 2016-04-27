#!/bin/bash
# Install Qordoba Magento Extention 


read -p "Please enter the Qordoba Extension zip file path like :/var/www/qordoba-extension.zip : " ext_file
ext_file=${ext_file:-qordoba-extension.zip}

#unzip the Qordoba
echo "Unzip Qordoba extention"
unzip  -o $ext_file -d /tmp/qordoba-extension/


read -p "Please enter your magento root directory like /var/www/magento , use . if you are in the directory: " mage_root
mage_root=${mage_root:-.}

# Removing the old files
echo "Removing the old files"
cd $mage_root
rm -rf app/code/community/Qordoba/
rm -rf app/design/adminhtml/default/default/layout/qordoba/translator.xml
rm -rf app/design/adminhtml/default/default/template/connector/
rm -rf app/etc/modules/Qordoba_Connector.xml
rm -rf js/qordoba/
rm -rf lib/QordobaApi/
rm -rf skin/adminhtml/default/default/qordoba/
echo "Qordoba extention is removed"

echo "Copy new files to magento"
cd $mage_root
cp -r /tmp/qordoba-extension/magento-extension.git/app/code/community/Qordoba  app/code/community/
cp -r /tmp/qordoba-extension/magento-extension.git/app/design/adminhtml/default/default/layout/qordoba  app/design/adminhtml/default/default/layout/
cp -r /tmp/qordoba-extension/magento-extension.git/app/design/adminhtml/default/default/template/connector  app/design/adminhtml/default/default/template/connector/
cp /tmp/qordoba-extension/magento-extension.git/app/etc/modules/Qordoba_Connector.xml  app/etc/modules/
cp -r /tmp/qordoba-extension/magento-extension.git/js/qordoba  js/
cp -r /tmp/qordoba-extension/magento-extension.git/lib/QordobaApi  lib/
cp -r /tmp/qordoba-extension/magento-extension.git/skin/adminhtml/default/default/qordoba  skin/adminhtml/default/default/
echo "Files are copied"

rm -rf /tmp/qordoba-extension/


tar -cvzf 1.0.3.tgz *