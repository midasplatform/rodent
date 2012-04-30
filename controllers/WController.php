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
/** as controller*/
class Rodent_WController extends Rodent_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder');
  public $_components = array('Export');
// public $_moduleComponents = array('Executable', 'Job');
// public $_moduleModels = array('Job');

  protected $pipelinePrefix = "rodent_warp_";
  
  
  
  protected function exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport)
    {
    $itemIds = array();
    foreach($itemsForExport as $configParam => $itemId)
      {
      $itemIds[] = $itemId;
      }
      
    // export the items to the work dir data dir
    $datapath = $taskDao->getWorkDir() . '/' . 'data';
    if(!KWUtils::mkDir($datapath))
      {
      throw new Zend_Exception("couldn't create data export dir: ". $datapath);
      }
    $symlink = true;
    $this->Component->Export->exportBitstreams($userDao, $datapath, $itemIds, $symlink);

    // for each of these items, generate a path that points to a single bitstream
    
    // get the bitstream path, assuming latest revision of item, with one bitstream
    // this seems somewhat wrong, as we are halfway recreating the export
    // and dependent upon the export to work in a certain way for this to work
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('Item');
   
    $configParamsToBitstreamPaths = array();
    foreach($itemsForExport as $configParam => $itemId)
      {
      $itemDao = $itemModel->load($itemId);
      $revisionDao = $itemModel->getLastRevision($itemDao);
      $bitstreamDaos = $revisionDao->getBitstreams();
      if(empty($bitstreamDaos))
        {
        throw new Zend_Exception("Item ".$itemId." had no bitstreams.");
        }
      $imageBitstreamDao = $bitstreamDaos[0];
      $exportedBitstreamPath = $datapath . '/' . $itemId . '/' . $imageBitstreamDao->getName();
      $configParamsToBitstreamPaths[$configParam] = $exportedBitstreamPath;
      }
    return $configParamsToBitstreamPaths;
    }
  
  
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
    $this->view->header = "Warp Pipeline Wizard";
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();
    
    
    $folderSelections = array("outputdirectory" => "Output Directory",
        "casesdirectory" => "Cases Directory");
        //"externalatlasandprobmapdirectory" => "External atlas and probability map directory"
 
    $itemSelections = array("populationaverage" => "Computed population average",
        "segmentation" => "Segmentation",
        "labelmapstowarp" => "Label Maps To Warp",
        "templatemask" => "Template Mask",
	"template" => "Template");
    
    $parameters = array("useinvhfield" => array("type" => "boolean", "label" => "Using inv h-field?"),
        "newmasktag" => array("type" => "boolean", "label" => "Tag of the new mask"),
        "warpinputsuffix" => array("type" => "text", "label" => "Suffix of the warp input"),
        "invhfieldsuffix" => array("type" => "text", "label" => "Suffix of the inv h-field"),
        "iterationnumber" => array("type" => "text", "label" => "Iteration number"));
    
    $inputs = array("prefix" => $this->pipelinePrefix, "folders" => $folderSelections, "items" => $itemSelections, "parameters" => $parameters);
    $this->view->inputs = $inputs;
    $this->view->json['inputs'] = $inputs;
    
    
    }

  /**
* start a unu job, via an ajax call.
*/
  public function startjobAction()
    {
    $this->disableLayout();
    $this->disableView();
  
    // create a task
    $userDao = $this->userSession->Dao;
    $componentLoader = new MIDAS_ComponentLoader();
    $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
    $taskDao = $kwbatchmakeComponent->createTask($userDao);
    
    // export any data needed by the pipeline from midas
    $singleBitstreamItemParams = array("populationaverage" => "Computed population average",
        "segmentation" => "Segmentation",
        "labelmapstowarp" => "Label Maps To Warp",
        "templatemask" => "Template Mask",
	"template" => "Template");
    
    $singleBitstreamItemIds = array();

    // TODO need to keep cleaning up these exports, just working through params one at a time
    // as we develop the pipeline
    
    // first step is all items that have only one bitstream
    $inputParams = $this->_getAllParams();
    $configInputs = array();
    $substrInd = strlen($this->pipelinePrefix);
    foreach($inputParams as $inputParam => $value)
      {
      if(strpos($inputParam, $this->pipelinePrefix) === 0)
        {
        // collect all config inputs
        $configInputs[substr($inputParam, $substrInd)] = $value;
        // find the items needed to export
        $paramName = substr($inputParam, $substrInd);
        if(array_key_exists($paramName, $singleBitstreamItemParams))
          {
          $singleBitstreamItemIds[$paramName] = $value;
          }
        }
      }
    
    $configParamsToBitstreamPaths = $this->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $singleBitstreamItemIds);

    // replace any exported item config params with their path values
    foreach($configParamsToBitstreamPaths as $configInput => $bitstreamPath)
      {
      $configInputs[$configInput] = $bitstreamPath;
      }
    
    
    // now that we have created a task, create a new folder for this task under
    // the outputFolder
    $outputFolderId = $configInputs["outputdirectory"];
    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');
    $outputFolderDao = $folderModel->createFolder('W task ' . $taskDao->getKey() . ' Output', '', $outputFolderId);
    // now set the outputFolderId to be the newly created one
    $outputFolderId = $outputFolderDao->getKey();
    
    // generate and export midas client communication params
    $executeComponent = $componentLoader->loadComponent('Execute', 'rodent');
    $executeComponent->generatePythonConfigParams($taskDao, $userDao);

    
    $condorPostScriptPath = BASE_PATH . '/modules/rodent/library/w_condor_postscript.py';
    $configScriptStem = "w";

    $executeComponent->generateBatchmakeConfig($taskDao, $configInputs, $condorPostScriptPath, $configScriptStem);
  
    // export the batchmake scripts
    $bmScript = "w.pipeline.bms";
    $kwbatchmakeComponent->preparePipelineScripts($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->preparePipelineBmms($taskDao->getWorkDir(), array($bmScript));

    // generate and run the condor dag
    $kwbatchmakeComponent->compileBatchMakeScript($taskDao->getWorkDir(), $bmScript);
    $dagScript = $kwbatchmakeComponent->generateCondorDag($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);
    
    
    
/* $bmScript = "unu.bms";
$kwbatchmakeComponent->preparePipelineScripts($taskDao->getWorkDir(), $bmScript);
$kwbatchmakeComponent->preparePipelineBmms($taskDao->getWorkDir(), array($bmScript));
$kwbatchmakeComponent->compileBatchMakeScript($taskDao->getWorkDir(), $bmScript);
$dagScript = $kwbatchmakeComponent->generateCondorDag($taskDao->getWorkDir(), $bmScript);
$kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);
*/
    
    
    
    
    // todo export the input
    // can either do it here or pass down enough info to do it in a python script
    
    
     // outputdirectory
      
// $rest = substr("abcdef", -3, 1);
    //echo JsonComponent::encode($taskDao);
        //$this->view->json['inputParams'] = $inputParams;
  
/*
$inputItemId = $this->_getParam("inputItemId");
$outputFolderId = $this->_getParam("outputFolderId");
$aValue = $this->_getParam("aValue");
$pValue = $this->_getParam("pValue");


// export the input item
$itemIds = array($inputItemId);
$this->exportItemsToWorkDataDir($userDao, $taskDao, $itemIds);


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

*/
  }
    
  
  



}//end class