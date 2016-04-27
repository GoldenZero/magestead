<?php

/**
 * Description of Status
 *
 * @author Qordoba
 */
class Qordoba_Connector_Model_Source_Content_Status
{
   
    /**
     * 
     * @return array
     */
    public function getOptions(){
        return array(
            Qordoba_Connector_Model_Content::CONTENT_STATUS_PROCESS   => 'In Progress',
            Qordoba_Connector_Model_Content::CONTENT_STATUS_COMPLETED => 'Completed',
        );
    }
}
