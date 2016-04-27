<?php

/**
 * Simple content node
 *
 * @author Qordoba
 */
class  Qordoba_Connector_Model_Types_Content extends Qordoba_Connector_Model_Types_Abstract
{
    /**
     * 
     * @param string $value
     * @param array $attributes
     */
    public function setContent($value, $attributes) {
        
        $this->_contentInstance->setTranslateContent(
                                    $value, 
                                    array(
                                        'attribute' => $attributes['attribute'],
                                        'type' => $attributes['type']
                                    ));
        
    }
}