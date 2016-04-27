<?php

/**
 * Module Version Info
 *
 * @author Qordoba
 */
class Qordoba_Connector_Block_Adminhtml_System_Config_Version
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * 
     * @param \Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(\Varien_Data_Form_Element_Abstract $element) {
        return (string) Mage::getConfig()->getNode()->modules->Qordoba_Connector->version;
    }

}