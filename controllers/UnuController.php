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
/** unu controller*/
class Rodent_UnuController extends Rodent_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder');
  public $_components = array('Export');
//  public $_moduleComponents = array('Executable', 'Job');
//  public $_moduleModels = array('Job');

  
  
  
  protected function exportItemsToWorkDataDir($userDao, $taskDao, $itemIds)
    {
    // export the items to the work dir data dir
    $datapath = $taskDao->getWorkDir() . '/' . 'data';
    if(!KWUtils::mkDir($datapath))
      {
      throw new Zend_Exception("couldn't create data export dir: ". $datapath);
      }
    //$componentLoader = new MIDAS_ComponentLoader();
    //$exportComponent = $this->$componentLoader->loadComponent('Export');
    $symlink = true;
    $this->Component->Export->exportBitstreams($userDao, $datapath, $itemIds, $symlink);
    }
  
  
  
  
  
  
  

  /** init a job*/
  function initAction()
    {
    $this->view->header = "Unu Pipeline Wizard";
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();
    }

  /** 
   * start a unu job, via an ajax call.
   */
  public function startjobAction()
  {
  $this->disableLayout();
  $this->disableView();
  $inputItemId = $this->_getParam("inputItemId");
  $outputFolderId = $this->_getParam("outputFolderId");
  $aValue = $this->_getParam("aValue");
  $pValue = $this->_getParam("pValue");

  $userDao = $this->userSession->Dao;
  $componentLoader = new MIDAS_ComponentLoader();
  $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
  $taskDao = $kwbatchmakeComponent->createTask($userDao);

  // now that we have created a task, create a new folder for this task under
  // the outputFolder
  $modelLoad = new MIDAS_ModelLoader();
  $folderModel = $modelLoad->loadModel('Folder');
  $outputFolderDao = $folderModel->createFolder('UNU task ' . $taskDao->getKey() . ' Output', '', $outputFolderId);
  // now set the outputFolderId to be the newly created one
  $outputFolderId = $outputFolderDao->getKey();
  
  // export the input item
  $itemIds = array($inputItemId);
  $this->exportItemsToWorkDataDir($userDao, $taskDao, $itemIds);

  $executeComponent = $componentLoader->loadComponent('Execute', 'rodent');
  $executeComponent->generatePythonConfigParams($taskDao, $userDao);
        
  $condorPostScriptPath = BASE_PATH . '/modules/rodent/library/unu_condor_postscript.py';
  $configScriptStem = "unu";
  
  // get the bitstream path, assuming latest revision of item, one bitstream in item
  // don't really like this, halfway recreating the export, and dependent upon
  // the export to work in a certain way for this to work
  $datapath = $taskDao->getWorkDir() . 'data/';
  $modelLoad = new MIDAS_ModelLoader();
  $itemModel = $modelLoad->loadModel('Item');
  $itemDao = $itemModel->load($inputItemId);
  $revisionDao = $itemModel->getLastRevision($itemDao);
  $bitstreamDaos = $revisionDao->getBitstreams();
  if(empty($bitstreamDaos))
    {
    throw new Zend_Exception("This item had no bitstreams.");
    }
  $imageBitstreamDao = $bitstreamDaos[0];
  $exportedBitstreamPath = $datapath . $inputItemId . '/' . $imageBitstreamDao->getName();
  
  $appTaskConfigProperties = array();
  $appTaskConfigProperties['cfg_inputImagePath'] = $exportedBitstreamPath;
  $appTaskConfigProperties['cfg_outputFolderId'] = $outputFolderId;
  $appTaskConfigProperties['cfg_aValue'] = $aValue;
  $appTaskConfigProperties['cfg_pValue'] = $pValue;

  $executeComponent->generateBatchmakeConfig($taskDao, $appTaskConfigProperties, $condorPostScriptPath, $configScriptStem);
  
  $bmScript = "unu.bms";
  $kwbatchmakeComponent->preparePipelineScripts($taskDao->getWorkDir(), $bmScript);
  $kwbatchmakeComponent->preparePipelineBmms($taskDao->getWorkDir(), array($bmScript));
  $kwbatchmakeComponent->compileBatchMakeScript($taskDao->getWorkDir(), $bmScript);
  $dagScript = $kwbatchmakeComponent->generateCondorDag($taskDao->getWorkDir(), $bmScript);
  $kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);

  
  $this->view->json['inputItemId'] = $inputItemId;
  }
    
  
  
  
  
  

  
  
  
  
  
  
  
    
  /** return the executable form (should be an ajax call) */
  function getinitexecutableAction()
    {
    $this->disableLayout();
    $itemId = $this->_getParam("itemId");
    $scheduled = $this->_getParam("scheduled");
    if(isset($scheduled) && $scheduled == 1)
      {
      $scheduled = true;
      }
    else
      {
      $scheduled = false;
      }

    $this->view->scheduled = $scheduled;
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }

    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }

    $metaFile = $this->ModuleComponent->Executable->getMetaIoFile($itemDao);
    if($metaFile == false)
      {
      throw new Zend_Exception("Unable to find meta information");
      }

    $metaContent = new SimpleXMLElement(file_get_contents($metaFile->getFullPath()));
    $this->view->metaContent = $metaContent;

    $this->view->itemDao = $itemDao;
    $this->view->json['item'] = $itemDao->toArray();
    }

  /** view a job */
  function viewAction()
    {
    $this->view->header = $this->t("Job");
    $jobId = $this->_getParam("jobId");
    $jobDao = $this->Remoteprocessing_Job->load($jobId);
    if(!$jobDao)
      {
      throw new Zend_Exception("Unable to find job.");
      }

    $this->view->job = $jobDao;
    $this->view->header = $this->t("Job: ".$jobDao->getName());
    $items = $jobDao->getItems();
    $inputs = array();
    $outputs = array();
    $parametersList = array();
    $executable = false;
    $log = false;

    foreach($items as $key => $item)
      {
      if(!$this->Item->policyCheck($item, $this->userSession->Dao))
        {
        unset($items[$key]);
        continue;
        }
      if($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_EXECUTABLE)
        {
        $executable = $item;
        }
      elseif($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_INPUT)
        {
        $inputs[$item->getName()] = $item;
        }
      elseif($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_OUPUT)
        {
        $reviesion = $this->Item->getLastRevision($item);
        $metadata = $this->ItemRevision->getMetadata($reviesion);
        $item->metadata = $metadata;

        foreach($metadata as $m)
          {
          if($m->getElement() == 'parameter' && !in_array($m->getQualifier(), $parametersList))
            {
            $parametersList[$m->getQualifier()] = $m->getQualifier();
            }
          $item->metadataParameters[$m->getQualifier()] = $m->getValue();
          }

        $outputs[] = $item;
        }
      elseif($item->type == MIDAS_REMOTEPROCESSING_RELATION_TYPE_RESULTS)
        {
        $reviesion = $this->Item->getLastRevision($item);
        $metadata = $this->ItemRevision->getMetadata($reviesion);
        $item->metadata = $metadata;

        $bitstreams = $reviesion->getBitstreams();
        if(count($bitstreams) == 1)
          {
          $log = file_get_contents($bitstreams[0]->getFullPath());
          }
        }
      }

    $this->view->outputs = $outputs;
    $this->view->log = $log;
    $this->view->results =  $this->ModuleComponent->Job->convertXmlREsults($log);
    $this->view->inputs = $inputs;
    $this->view->executable = $executable;
    $this->view->parameters = $parametersList;
    }




}//end class
