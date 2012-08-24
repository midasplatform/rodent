<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/
require_once BASE_PATH . '/modules/api/library/APIEnabledNotification.php';

/** notification manager*/
class Rodent_Notification extends ApiEnabled_Notification
  {

  public $moduleName = 'rodent';
  public $_moduleComponents=array('Api', 'Execute');

  /** init notification process*/
  public function init()
    {
    $this->enableWebAPI($this->moduleName);
    $this->addCallBack('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLink');
    }//end init

  /**
   *@method getLeftLink
   * will generate a link for this module to be displayed in the main view.
   *@return ['rodent imaging' => [ link to rodent module, module icon image path]]
   */
  public function getLeftLink()
    {
    $fc = Zend_Controller_Front::getInstance();
    $baseURL = $fc->getBaseUrl();
    $moduleWebroot = $baseURL . '/rodent';
    return array('Rodent Imaging' => array($moduleWebroot . '/index',  $baseURL . '/modules/rodent/public/images/rat.png'));
    }


  } //end class
?>
