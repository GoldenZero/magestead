<?php

/**
 * Description of Content
 *
 * @author Qordoba
 */
class Qordoba_Connector_Block_Adminhtml_Projects
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    
    public function __construct() {
                
        $this->_controller = "adminhtml_projects";
        $this->_blockGroup = "connector";
        $this->_headerText = Mage::helper('connector')->__('Settings profiles');        
                
        parent::__construct();
    }
    
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new');
    }
}
