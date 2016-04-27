<?php

/**
 * Description of Types
 *
 * @author Itdelight
 */
class Qordoba_Connector_Model_Source_Types
{
    
    /**
     * 
     * @return array
     */
    public function toOptionArray(){
        return array(
            array(
                'value' => Qordoba_Connector_Model_Translator::FILE_TYPE_JSON,
                'label' => 'JSON',
            ),
            array(
                'value' => Qordoba_Connector_Model_Translator::FILE_TYPE_XML,
                'label' => 'XML',
            ),
        );
    }
}
