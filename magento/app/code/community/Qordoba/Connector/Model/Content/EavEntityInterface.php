<?php 

/**
 * Workflow for atributes with options and any system entities relation to EAV models
 * 
 */
    interface Qordoba_Connector_Model_Content_EavEntityInterface {
        public function getTranslatedAttributesOptions($content);
    }