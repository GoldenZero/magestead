<?php

/**
 * Description of Translator
 *
 * @author Itdelight
 */
class Qordoba_Connector_Adminhtml_TranslatorController
    extends Mage_Adminhtml_Controller_Action
{

    public function indexAction() {
        $this->_redirect('*/*/send');
    }

    /**
     * needs for tet
     */
    public function allAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * shows log file with actions
     */
    public function logsAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Download log file if exists
     */
    public function downloadlogAction(){

        $filename = Mage::getBaseDir('var') . DS . 'log' . DS . Mage::getStoreConfig('dev/qordoba/log_file');

        if(!file_exists($filename)) {
            $this->_redirectReferer();
        }

        $content = file_get_contents($filename);
        $basename = basename($filename);

        header('Content-disposition: attachment; filename=' . $basename);
        header('Content-type: text/plain');
        echo $content;

        exit();
    }

    /**
     * Download log file if exists
     */
    public function removelogAction(){

        $filename = Mage::getBaseDir('var') . DS . 'log' . DS . Mage::getStoreConfig('dev/qordoba/log_file');

        if(file_exists($filename)) {

            try {

                if(!unlink($filename)) {
                    Mage::throwException("Sorry. File can't be removed. See exception log for more details.");
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('connector')->__("Log file has been removed successfully.")
                );

            } catch (Mage_Core_Exception $ex) {

                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('connector')->__($ex->getMessage())
                );

                Mage::logException($ex);
                Mage::helper('connector')->log($ex->getMessage(), Zend_log::ERR);
            }

        }

        $this->_redirectReferer();
    }

    /**
     * show logs with errors
     */
    public function errorsAction(){
        $this->loadLayout();
        $this->getLayout()->getBlock('actions.logs')->setFilename('Response.log');
        $this->renderLayout();
    }

    /**
     * use for Ajax only
     */
    public function gridAction(){
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('connector/adminhtml_content_grid')
                ->toHtml()
        );
    }

    /**
     * download file
     */
    public function downloadAction() {
        $contentId = $this->getRequest()->getParam('content_id');

        $errors = array();
        if (!$contentId || (string) $contentId === '') {
            $errors[] = "You have to specify content item first";
        }

        $projectsLocales = Mage::getModel('connector/projects_locales');

        try {

            if (sizeof($errors)) {
                Mage::throwException('Submission has errors');
            }

            //@TODO add validation for projects IDs here

            try {
                $this->updateStatus($contentId);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }

        } catch (Mage_Core_Exception $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = Mage::helper('connector')->__('Sorry. Internal application error.');
        }

        if (sizeof($errors) > 0) {
            for ($i = sizeof($errors); $i--;) {
                Mage::getSingleton('adminhtml/session')->addError($errors[$i]);
            }
        }

        if($this->getRequest()->isAjax()) {
            $this->loadLayout();

            $block = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
            $result = array(
                'messageblock' => $block,
            );

            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_redirectReferer();
        }
    }

    /**
     * We need:
     * - find items in qordoba translation table (connector/content)
     * foreach item:
     * - if items locale is in locales array try do download content from Qordoba API (connector/translator)
     * - if content isn't null - create new content item (connector/content_abstract)
     * - after that we need to update table with new content id (connector/content)
     */
    public function downloadsingleAction() {

        $data = $this->getRequest()->getParams();
        $origin_content_id = $data['content_id'];
        $errors = array();
        $locales = array();

        if (!isset($data['content_type']) || (string) $data['content_type'] === '') {
            $errors[] = Mage::helper('connector')->__("Content type can't be empty");
        }

        if(isset($data['locales'])) {
            $locales = $data['locales'];
        }

        if (!sizeof($locales)) {
            $errors[] = Mage::helper('connector')->__("Please specify locales");
        }

        if (!isset($data['content_id']) || (string) $data['content_id'] === '') {
            $errors[] = Mage::helper('connector')->__("You have to specify content item first");
        } else { // check if record is ready to apply translated content

            $contentModel = Mage::getModel('connector/content');

            foreach($locales as $project_id => $locale) {
                for ($i = 0; $i < sizeof($locale); $i++) {

                    $locale_name = Mage::getSingleton('connector/projects')->getLocaleTitle($locale[$i]);

                    $contentcollection = $contentModel->getCollectionByUniqueKeys($origin_content_id, $project_id, $locale[$i]);
                    $item = $contentcollection->getFirstItem();

                    if(!sizeof($item->getData())) {
                        $errors[] = Mage::helper('connector')->__("Sorry, content not found in submission queue. Locale [%s]", $locale_name);
                        continue;
                    }

                    $checkStatusErrors = $contentModel->checkReadyForDownload($item->getStatus(), $locale_name);

                    if(sizeof($checkStatusErrors)) {
                        $errors = array_merge($errors, $checkStatusErrors);
                    }
                }
            }
        }

        $projectsLocales = Mage::getModel('connector/projects_locales');

        try {

            if (sizeof($errors)) {
                Mage::throwException(
                    Mage::helper('connector')->__('Submission has errors')
                );
            }

            $contentModelClass = Mage::getModel('connector/content_types')->load($data['content_type']);
            if (!$contentModelClass->getId()) {
                $message = Mage::helper('connector')->__("Unknown content type");
                Mage::throwException($message);
            }

            $contentTypeTitleSingle = strtolower($contentModelClass->getTitleSingle());

            foreach($locales as $project_id => $locale) {
                for ($i = 0; $i < sizeof($locale); $i++) {

                    try {

                        $localeCode = $projectsLocales->getLocaleCodeByStoreId($locale[$i]);
                        $localeName = $projectsLocales->getLocaleNameByStoreId($locale[$i]);

                        if(!$localeCode) {
                            $message = Mage::helper('connector')
                                ->__("Unknown locale ID - %s", $locale[$i]);
                            throw new Exception($message);
                        }

                        $serviceModel = Mage::getModel('connector/service');
                        $serviceModel->setOriginContentId($origin_content_id);
                        $serviceModel->setProjectId($project_id);
                        $serviceModel->setStoreId($locale[$i]);

                        $result = $serviceModel->updateStatus(true);

                        if($result) {
                            Mage::getSingleton('adminhtml/session')->addSuccess(
                                Mage::helper('connector')->__("The translated content has been added to the %s in your %s store.",
                                    $contentTypeTitleSingle, $localeName)
                            );
                        } else {
                            Mage::getSingleton('adminhtml/session')->addError(
                                Mage::helper('connector')->__("The %s content of the %s has not been translated yet.",
                                    $localeName, $contentTypeTitleSingle)
                            );
                        }

                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }

                }
            }


        } catch (Mage_Core_Exception $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = Mage::helper('connector')->__('Sorry. Internal application error.');
        }

        if (sizeof($errors) > 0) {
            foreach($errors as $error) {
                Mage::helper('connector')->log('Download single action. ' . $error, Zend_log::ERR);
                Mage::getSingleton('adminhtml/session')->addError($error);
            }
        }

        if($this->getRequest()->isAjax()) {

            $this->loadLayout();

            $block = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
            $result = array(
                'messageblock' => $block,
            );

            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_redirectReferer();
        }
    }

    /**
     * update file content status
     * check comoletion process
     */
    public function updateAction(){
        $content = $this->getRequest()->getParam('content');
        $errors = array();

        if (!is_array($content)){
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('connector')->__('Incorrect data type')
            );
            $this->_redirectReferer();
            return;
        }

        $count = sizeof($content);

        for ($i = 0; $i < $count; $i++){
            try {
                $this->updateStatus($content[$i]);
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $contentModel = Mage::getModel('connector/content');

        if (!empty($errors)) {
            Mage::getSingleton('adminhtml/session')->addError(implode("; ", array_unique($errors)));
        }
        $this->_redirectReferer();
        return;
    }

    /**
     * Upload single content to Qordoba
     *
     * @return void
     */
    public function uploadsingleAction() {
        $data = $this->getRequest()->getParams();

        $countAddedItem = 0;
        $locales = array();
        $message = '';

        $errors = array();
        if (!isset($data['content_type']) || (string) $data['content_type'] === '') {
            $errors[] = Mage::helper('connector')->__("Content type can't be empty");
        }

        if (!isset($data['content_id']) || (string) $data['content_id'] === '') {
            $errors[] = Mage::helper('connector')->__("You have to specify content item first");
        }

        if(isset($data['locales'])) {
            $locales = $data['locales'];
        }

        if (!is_array($locales)) {
            $errors[] = Mage::helper('connector')->__("Please specify locales");
        }

        try {

            if (sizeof($errors)) {
                Mage::throwException(
                    Mage::helper('connector')->__('Submission has errors')
                );
            }

            //@TODO add validation for projects IDs here


            $contentModelClass = Mage::getModel('connector/content_types')->load($data['content_type']);
            if (!$contentModelClass->getId()) {
                $message = Mage::helper('connector')->__("Unknown content type");
                Mage::throwException($message);
            }

            $contentTypeTitleSingle = strtolower($contentModelClass->getTitleSingle());

            /** @var Qordoba_Connector_Model_Content_Abstract */
            $contentModel = Mage::getModel($contentModelClass->getModel());
            $projectsLocales = Mage::getModel('connector/projects_locales');

            $processData = array(
                'type'              => $data['content_type'],
                'origin_content_id' => $data['content_id']
            );

            foreach($locales as $project_id => $locale) {

                $processData['project_id'] = $project_id;

                for ($i = 0; $i < sizeof($locale); $i++) {

                    try {

                        $localeCode = $projectsLocales->getLocaleCodeByStoreId($locale[$i]);
                        $localeName = $projectsLocales->getLocaleNameByStoreId($locale[$i]);

                        if(!$localeCode) {
                            $message = Mage::helper('connector')
                                ->__("Unknown locale ID - %s", $locale[$i]);
                            throw new Exception($message);
                        }

                        $result = Mage::getResourceModel('connector/content')
                            ->addSingleItem($locale[$i], $processData);

                        if ($result == '-1') {
                            $message = Mage::helper('connector')
                                ->__('Attributes for translation are not specified');
                            Mage::getSingleton('adminhtml/session')->addError($message);
                        } elseif ($result && $result !== 0) {
                            $message = Mage::helper('connector')
                                ->__('1 %s uploaded to Qordoba for translation to %s',
                                    $contentTypeTitleSingle, $localeName);
                            Mage::getSingleton('adminhtml/session')->addSuccess($message);
                            $countAddedItem++;
                        } elseif ($result == 0) {
                            $message = Mage::helper('connector')
                                ->__('This %s already exists in your Qordoba project for %s.',
                                    $contentTypeTitleSingle, $localeName);
                            Mage::getSingleton('adminhtml/session')->addSuccess($message);
                        } else {
                            $message = Mage::helper('connector')
                                ->__('This %s failed to send. Please try again later.', $contentTypeTitleSingle);
                            throw new Exception($message);
                        }

                    } catch (Exception $e) {
                        $errors[] = $e->getMessage();
                    }

                }
            }

            Mage::dispatchEvent('qordoba_push_to_queue_after', array('model_instance' => $contentModel));

        } catch (Mage_Core_Exception $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = Mage::helper('connector')->__('Sorry. Internal application error.');
        }

        if (sizeof($errors) > 0) {
            for ($i = sizeof($errors); $i--;) {
                Mage::helper('connector')->log($errors[$i], Zend_log::ERR);
                Mage::getSingleton('adminhtml/session')->addError($errors[$i]);
            }
        } elseif(Mage::getStoreConfig('dev/qordoba/mode') == 1) {
            Mage::helper('connector')->scheduleNow();
        }

        $this->loadLayout();
        $block = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
        $result = array(
            'messageblock' => $block,
        );

        if($countAddedItem > 1) {
            $message = Mage::helper('connector')->__('Qordoba Upload Single - %d nodes were added to queue', $countAddedItem);
        } elseif($countAddedItem == 1) {
            $message = Mage::helper('connector')->__('Qordoba Upload Single - 1 node was added to queue');
        }

        Mage::helper('connector')->log($message, Zend_log::INFO);

        if($this->getRequest()->isAjax()) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_redirectReferer();
        }
    }

    public function checkstatusAction() {

        $data = $this->getRequest()->getParams();

        // collection cache tag is harcoded in Mage_Core_Model_Resource_Db_Collection_Abstract extends Varien_Data_Collection_Db
        $collection_cache_tag = 'collections';

        $isCollectionCacheEnabled = Mage::app()->useCache($collection_cache_tag);
        if($isCollectionCacheEnabled) {
            $cacheKey = md5(serialize($data));
        }

        $result = array();
        $errors = array();

        try {

            if (!isset($data['content_id']) || (string) $data['content_id'] === '') {
                Mage::throwException(
                    Mage::helper('connector')->__("You have to specify content item first")
                );
            }

            if($isCollectionCacheEnabled) {
                $cachedData = Mage::app()->loadCache($cacheKey);

                if($cachedData) {
                    $result = unserialize($cachedData);

                    if($this->getRequest()->isAjax()) {
                        return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    }
                }
            }

            $content_id  = $data['content_id'];
            $result['element_id'] = $content_id;

            $contentCollection = Mage::getModel('connector/content')->getCollection();
            $contentCollection->addIndentifyToFilter($content_id);
            $contentCollection->addFieldToFilter('filename', array('neq' => ''));
            $contentCollection->joinAdditionalDetails();

            // if item is not uploaded
            if($contentCollection->getSize() == 0) {
                $result['percent'] = 0;

                if($this->getRequest()->isAjax()) {
                    return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                } else {
                    return false;
                }
            }

            $item = $contentCollection->getFirstItem();

            try {

                $connector = Mage::getModel('connector/translator',
                    array(
                        'apiKey' => $item['api_key'],
                        'projectId' => $item['project_code'],
                        'project_id' => $item['project_id']
                    )
                );



                // check status of translation
                $statusResponse = $connector->checkStatus($item['file_id'], $item['locale']);

                // check of unexpected format
                if (!Mage::helper('connector')->isJson($statusResponse)) {
                    Mage::throwException(
                        Mage::helper('connector')
                            ->__('Invalid response. Expected type is JSON. Received: ')
                        . $statusResponse
                    );
                } else {
                    $statusResponseData = json_decode($statusResponse, true);
//                            $responseData = array();
//
//                            if(isset($statusResponseData['response'])) {
//                                $responseData = $statusResponseData['response'];
//                            }
                    $responseData = $statusResponseData;

                    if($responseData['result'] != 'success') {
//                                Mage::throwException(implode('; ', $responseData['messages']));
                        Mage::throwException('File status was not found');
                    }

                    $percent =
                        Mage::helper('connector')->calculatePercent(
                            @$responseData['complete_segment_count'],
                            @$responseData['segment_count']
                        );

                    $result['percent'] = $percent;
                }


            } catch (Mage_Core_Exception $e) {
                Mage::helper('connector')->log($e->getMessage(), Zend_log::ERR);
            } catch (Exception $e) {
                Mage::helper('connector')->log($e->getMessage(), Zend_log::ERR);
            }

        } catch (Mage_Core_Exception $e) {
            $errors[] = $e->getMessage();
        } catch (Exception $e) {
            $errors[] = Mage::helper('connector')->__('Sorry. Internal application error.');
        }

        if (sizeof($errors) > 0) {
            for ($i = sizeof($errors); $i--;) {
                Mage::helper('connector')->log($errors[$i], Zend_log::ERR);
            }
        }

        if($isCollectionCacheEnabled) {
            Mage::app()->saveCache(serialize($result), $cacheKey,
                array($collection_cache_tag),
                Mage::getStoreConfig('dev/qordoba/cache_statuses_life_time')
            );
        }


        if($this->getRequest()->isAjax()) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Call update status request
     * @param int $content_id
     * @return boolean
     */
    protected function updateStatus($content_id) {
        $serviceModel = Mage::getModel('connector/service');
        $serviceModel->setContentId($content_id);
        $result = $serviceModel->updateStatus(true);

        if($result) {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('connector')->__("The translated content has been added to these items")
            );
            return true;
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('connector')->__("The content of these items has not been translated yet.")
            );
            return false;
        }
    }
}
