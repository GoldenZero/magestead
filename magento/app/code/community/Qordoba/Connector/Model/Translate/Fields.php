<?php

/**
 * Description of Content
 *
 * @author Qordoba
 */
class Qordoba_Connector_Model_Translate_Fields
    extends Mage_Core_Model_Abstract
{
    
    /**
     * set resource model
     * 
     */
    protected function _construct() {
        $this->_init('connector/translate_fields');
    }
}
