<?php

/**
 * Description of Types
 *
 * @author Qordoba
 */
class Qordoba_Connector_Model_Resource_Translate_Attributes
    extends Mage_Core_Model_Resource_Db_Abstract
{
    
    /**
     * define table and table id
     */
    protected function _construct() {
        $this->_init('connector/translate_attributes', 'id');
    }
    
}

