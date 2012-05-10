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
    
    
    $casesSelection = array('id'=> "casesdirectory", 'label' => "Select the Cases Directory");

    $multiItemSelections = array("rregfiles" => "Rreg files",
        "templatefiles" => "Template files");

    $singleItemSelections = array("templategridfile" => array("label" => "Template grid file", "bitstreamCount" => "single"));
    
/*    $itemSelections = array("rregfiles" => "Rreg files",
        "templatefiles" => "Template files",
        "templategridfile" => "Template grid file");
*/    
    $parameters = array("newmasktag" => array("type" => "text", "label" => "Tag of the mask that is gonna be created"),
	"rigid" => array("type" => "boolean", "label" => "Using rigid transformation? (usually checked)"),
        "registration" => array("type" => "boolean", "label" => "Do registration? (usually checked)"),
        "biasfieldcorrection" => array("type" => "boolean", "label" => "Use bias field correction?"),
        "rigidisFA" => array("type" => "boolean", "label" => "rigidisFA"),
        "scalar" => array("type" => "boolean", "label" => "Is the input scalar?"),
        "scaled" => array("type" => "boolean", "label" => "Is the input scaled?"),
        "filtercurvature" => array("type" => "boolean", "label" => "filtercurvature? (usually checked)"),
        "segimagestype" => array("type" => "text", "label" => "Suffix of the images used for segmentation (usually the same suffix given on folder window)"),
        "radius" => array("type" => "integer", "label" => "radius (usually 5)"),
        "abcpriors" => array("type" => "text", "label" => "abcpriors (usually 1 1 1 1)"),
        "rigidisMD" => array("type" => "boolean", "label" => "rigidisMD"),
        "sequence" => array("type"=>"text", "label"=>"sequence 0 NB_LOOPS 1 (usually 0 0 1)"));
    
    $inputs = array("prefix" => $this->pipelinePrefix, "cases" => $casesSelection, "multiItems" => $multiItemSelections, "singleItems" => $singleItemSelections, "parameters" => $parameters);
    //$inputs = array("prefix" => $this->pipelinePrefix, "folders" => $folderSelections, "items" => $itemSelections, "parameters" => $parameters);

// can be pulled out into a method
    $processSteps = array();
    $processStepInd = 1;
    if(array_key_exists("cases", $inputs)) {
        $processSteps[$processStepInd++] = array('title'=>'Select Cases', 'type' => 'cases', 'id'=> "casesdirectory");
    }
    if(array_key_exists("multiItems", $inputs)) {
        $multiItems = $inputs["multiItems"];
        foreach($multiItems as $id => $title) {
            $processSteps[$processStepInd++] = array('title' => $title, 'label'=>'Select '. $title, 'type' => 'multiItems', 'id' => $id);
        }
    }
    if(array_key_exists("singleItems", $inputs)) {
        $processSteps[$processStepInd++] = array('title'=>'Select Items', 'type' => 'singleItems');
    }
    if(array_key_exists("parameters", $inputs)) {
        $processSteps[$processStepInd++] = array('title'=>'Select Parameters', 'type' => 'parameters');
    }
//
    
    $this->view->processSteps = $processSteps;
    $this->view->inputs = $inputs;
    $this->view->json['inputs'] = $inputs;
    $this->view->json['processSteps'] = $processSteps;
    
    
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
    $singleBitstreamItemParams = array("templategridfiles" => "Template grid file");
    
    $singleBitstreamItemIds = array();

    // process the input params
    $inputParams = $this->_getAllParams();
    $configInputs = array();
    $substrInd = strlen($this->pipelinePrefix);
    $caseFolderPrefix = $this->pipelinePrefix . "casefolder_";
    $caseFolderSuffix = $this->pipelinePrefix . "case_suffix";
    $caseFolderSubstrInd = strlen($caseFolderPrefix);
    $multiitemPrefix = $this->pipelinePrefix . "multiitem_";
    $multiitemSubstrInd = strlen($multiitemPrefix);
    $caseFolders = array();
    $multiitems = array();
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
          $caseSuffix = $value;   
          }
        else if(strpos($inputParam, $multiitemPrefix) === 0)
          {
          $paramIdAndItemId = substr($inputParam, $multiitemSubstrInd);
          // find the last _, as id could have _ in it
          $lastUnderscoreInd = strrpos($paramIdAndItemId, "_");
          $itemId = substr($paramIdAndItemId, $lastUnderscoreInd+1);
          $paramId = substr($paramIdAndItemId, 0, $lastUnderscoreInd);
          if(!array_key_exists($paramId, $multiitems)) 
            {
            $multiitems[$paramId] = array();  
            }
          $multiitems[$paramId][] = $itemId;  
          }
        else
          {
          // upper case boolean values for BatchMake
          // TODO should have a better handler for this
          if($value === 'true')
            {
            $value = "TRUE";
            }
          if($value === 'false')
            {
            $value = "FALSE";
            }

            
            
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
    $executeComponent->exportCases($userDao, $taskDao, $configInputs, $caseFolders, $caseSuffix, "2-Registration");  

    // specific export for the multiitems chosen
    $executeComponent->exportMultiitems($userDao, $taskDao, $configInputs, $multiitems);  
//need to do something with multiitem
//prefix_multiitem_id_itemid
//            combine them into a multiitem, make the multiitem a list, export everything the in the list, export as prefix_id

    
    
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
