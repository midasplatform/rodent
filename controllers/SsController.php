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
require_once BASE_PATH . '/modules/rodent/AppController.php';
/** as controller*/
class Rodent_SsController extends Rodent_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder');
  public $_components = array('Export');

  protected $pipelinePrefix = "rodent_skullstrip_";
  
    
  protected function exportCases($userDao, $taskDao, &$configInputs, $caseFolders, $suffix)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');
    // in each folder, look for an item matching folder+suffix
    $cases = array();
    $itemsForExport = array();
    foreach($caseFolders as $folderId)
      {
      $folder = $folderModel->load($folderId);
      $caseName = $folder->getName();
      $cases[] = $caseName;
      $soughtItem = $caseName . $suffix;
//TODO some different error checking/exceptions if don't have write access
//TODO yuck this looking for folder names is brittle, better would be metadata
      $folders = $folderModel->getChildrenFoldersFiltered($folder, $userDao, MIDAS_POLICY_WRITE);
      foreach($folders as $folder)
        {
        if($folder->getName() === "2-Registration")
          {
          $items = $folderModel->getItemsFiltered($folder, $userDao, MIDAS_POLICY_WRITE);
          foreach($items as $item)
            {
            if($item->getName() === $soughtItem)
              {
              $itemsForExport[$caseName] = $item->getItemId();  
              }
            }      
          }
        }
      }
    
    $componentLoader = new MIDAS_ComponentLoader();
    $executeComponent = $componentLoader->loadComponent('Execute', 'rodent');
    
    $casesToExportPaths = $executeComponent->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport);
    $caseInputs = array();
    $caseIndices = array();
    $caseInd = 0;
    foreach($cases as $case)
      {
      $caseInputs[] = $casesToExportPaths[$case];  
      $caseIndices[] = $caseInd++;
      }
    // create configInputs
    // a list of cases
    // a list of paths to the cases data
    // a list of case folders
    // a case index for each case
    $configInputs['cases'] = $cases;
    $configInputs['casesInputs'] = $caseInputs;
    $configInputs['caseFolderIds'] = $caseFolders;
    $configInputs['caseInds'] = $caseIndices;
    }

  
  
  

  /** init a job*/
  function initAction()
    {
    $this->view->header = "Skull Strip Pipeline Wizard";
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();
    
    
    $folderSelections = array(//"outputdirectory" => "Output Directory",
        "casesdirectory" => "Cases Directory");
        //"externalatlasandprobmapdirectory" => "External atlas and probability map directory"
 
    $itemSelections = array("maskfromsegmentation" => "Mask from segmentation",
        "templatefiles" => "Template file",
        "templategridfile" => "Template grid file",
        "probabilitymaps" => "Probability maps",
        "rreg param file" => "rreg parameter files");
    
    $parameters = array(
	"rigid" => array("type" => "boolean", "label" => "Using rigid transformation?"),
        "registration" => array("type" => "boolean", "label" => "Do registration?"),
        "biasfieldcorrection" => array("type" => "boolean", "label" => "Use bias field correction?"),
        "rigidisFA" => array("type" => "boolean", "label" => "rigidisFA"),
        "scalar" => array("type" => "boolean", "label" => "Is the input scalar?"),
        "scaled" => array("type" => "boolean", "label" => "Is the input scaled>"),
        "filtercurvature" => array("type" => "boolean", "label" => "filtercurvature"),
#        "regimageis" => array("type" => "select", "label" => "Type of the images used for registration", "options" => array("FA","MD","other")),
        "segimagestype" => array("type" => "text", "label" => "Type of the images used for segmentation"),
        "regimagessuffix" => array("type" => "text", "label" => "Suffix of the images used to register the input image to the template to compute segmentation"),
        "radius" => array("type" => "integer", "label" => "radius"),
        "abcpriors" => array("type" => "text", "label" => "abcpriors"),
        "rigidisMD" => array("type" => "boolean", "label" => "rigidisMD"),
        "sequence" => array("type"=>"text", "label"=>"sequence 0 NB_LOOPS 1"));
    
    $inputs = array("prefix" => $this->pipelinePrefix, "folders" => $folderSelections, "items" => $itemSelections, "parameters" => $parameters);
    $this->view->inputs = $inputs;
    $this->view->json['inputs'] = $inputs;
    
    
    }

  /**
   * start an SS job, via an ajax call.
   */
  public function startjobAction()
    {
    $this->disableLayout();
    $this->disableView();
  
    // create a task
    $userDao = $this->userSession->Dao;
    $componentLoader = new MIDAS_ComponentLoader();
    $executeComponent = $componentLoader->loadComponent('Execute', 'rodent');
    $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
    $taskDao = $kwbatchmakeComponent->createTask($userDao);
    
    // export any data needed by the pipeline from midas
    $singleBitstreamItemParams = array("maskfromsegmentation" => "Mask from segmentation",
        "templatefiles" => "Template files",
        "templategridfiles" => "Template grid file",
        "probabilitymaps" => "Probability maps",
        "rreg param file" => "rreg parameter files");
    
    $singleBitstreamItemIds = array();

    // process the input params
    $inputParams = $this->_getAllParams();
    $configInputs = array();
    $substrInd = strlen($this->pipelinePrefix);
    $caseFolderPrefix = $this->pipelinePrefix . "casefolder_";
    $caseFolderSuffix = $this->pipelinePrefix . "suffix";
    $caseFolderSubstrInd = strlen($caseFolderPrefix);
    $caseFolders = array();
    foreach($inputParams as $inputParam => $value)
      {
      if(strpos($inputParam, $this->pipelinePrefix) === 0)
        {
        if(strpos($inputParam, $caseFolderPrefix) === 0)
          {
          // get the case folders by id
          $folderId = substr($inputParam, $caseFolderSubstrInd);
          $caseFolders[] = $folderId;
          }
        else if(strpos($inputParam, $caseFolderSuffix) === 0)
          {
          $suffix = $value;   
          }
        else
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
      }

    // specific export for the cases chosen
    $this->exportCases($userDao, $taskDao, $configInputs, $caseFolders, $suffix);  

    // export remaining inputs  
    $configParamsToBitstreamPaths = $executeComponent->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $singleBitstreamItemIds);

    // replace any exported item config params with their path values
    foreach($configParamsToBitstreamPaths as $configInput => $bitstreamPath)
      {
      $configInputs[$configInput] = $bitstreamPath;
      }
    
/*
 * ignoring this output for now, will want to upload back into each of the casess
 *     
    // now that we have created a task, create a new folder for this task under
    // the outputFolder
    $outputFolderId = $configInputs["outputdirectory"];
    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');
    $outputFolderDao = $folderModel->createFolder('SS task ' . $taskDao->getKey() . ' Output', '', $outputFolderId);
    // now set the outputFolderId to be the newly created one
    $outputFolderId = $outputFolderDao->getKey();
*/
    
    
    
    $condorPostScriptPath = BASE_PATH . '/modules/rodent/library/ss_condor_postscript.py';
    $configScriptStem = "ss";
    $bmScript = "ss1.pipeline.bms";
    $executeComponent->executeScript($taskDao, $userDao, $condorPostScriptPath, $configScriptStem, $bmScript, $configInputs);

  }
    
  
  



}//end class
