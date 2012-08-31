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
abstract class Rodent_PipelineController extends Rodent_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder', 'Community');
  public $_components = array('Export');

  // a unique string for the pipeline, something like rodent_pipelinename_
  abstract function getPipelinePrefix();
  // title to display on UI wizard
  abstract function getUiTitle();
  
  // return an array if you want the user to be able to select cases for this pipeline
  // id will be a batchmake config variable, i.e. for the ss pipeline, would go in
  // ss.config.bms, and would create to a line in that file like:
  // Set(casesdirectory '455')
  // where 455 is the midas ID for the folder chosen where all of the case folders
  // are nested under
  abstract function getCasesSelection();
  
  abstract function getInputFolder();
  
  abstract function getMultiItemSelections();
  // this method will create a UI step that allows the user to select a single
  // individual midas item for each row, where the array key will be the batchmake config variable
  // return an empty array to have this step skipped in the UI
  abstract function getSingleItemSelections();
  
  // subclasses can override this method to have a different label for this step
  function getSingleItemDisplayLabel() { return "Select Items"; }
  
  abstract function getParameters();
  // subclasses can override this method to change the display label on the step
  function getParametersDisplayLabel() { return "Select Parameters"; }
  
  // should be an array with keys being any variables defined in the getSingleItemSelections
  // method that only have one bitstream (file), needed to know how to export out of Midas
  // onto the filesystem
  abstract function getSingleBitstreamItemParams();
  abstract function getPostscriptPath();
  abstract function getConfigScriptStem();
  abstract function getBmScript();
  abstract function getOutputFolderStem();

  function getInputFolderConnectedDropdowns() { return array(); }
  function getInputFolderMultiselects() { return array(); }
  // use like this in a subclass:
  //function getInputFolderMultiselects() 
  //  { 
  //  return array(
  //    "2-Registration" => array(
  //    array("label"=> "inputs", "varname" => "mcasesInputs")));
  //  }
  
  function getDefaultCasesFolder() { return array('folder_id' =>'455', 'folder_path' => 'Rodent/Public/RPV0002/cases'); }
  
  
  /** init a job*/
  
  function initAction()
    {

    $this->view->header = $this->getUiTitle();
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();
    
    $inputs = array("prefix" => $this->getPipelinePrefix());
    $inputs["cases"] = $this->getCasesSelection();
    $inputs["casesFolderNames"] = array_keys($this->getInputFolder());
    $inputs["caseFolderVariables"] = $this->getInputFolder();
    $inputs["caseFolderDropdownVariables"] = $this->getInputFolderConnectedDropdowns();
    $inputs["caseFolderMultiVariables"] = $this->getInputFolderMultiselects();
    
    $inputs["multiItems"] = $this->getMultiItemSelections();
    $inputs["singleItems"] = $this->getSingleItemSelections();
    $inputs["parameters"] = $this->getParameters();
    $inputs["controllerPath"] = $this->getConfigScriptStem();

    $defaultCasesFolder = $this->getDefaultCasesFolder();
    if($defaultCasesFolder !== '')
      {
      $inputs["defaultCasesFolder"] = $defaultCasesFolder;
      }

    
    
    $processSteps = array();
    $processStepInd = 1;
    if(array_key_exists("cases", $inputs)) {
        $processSteps[$processStepInd++] = array('title'=>'Select Cases', 'type' => 'cases', 'id'=> "casesdirectory");
    }
    if(array_key_exists("multiItems", $inputs)) {
        $multiItems = $inputs["multiItems"];
        foreach($multiItems as $id => $properties) {
            $title = $properties['label'];
            $currentMultiItem = array('title' => $title, 'label'=>'Select '. $title, 'type' => 'multiItems', 'id' => $id);

            if(array_key_exists('default', $properties))
              {
              $defaultFolderId = $properties['default']['folder'];
              $folder = $this->Folder->load($defaultFolderId);
              //keep calling get root on the folder to build up the path
              $namePath = $folder->getName();
              $root = $folder;
              $parent = $folder->getParent();
              while($parent !== false && intval($parent->getKey()) > 0)
                {
                $community = $this->Community->getByFolder($parent);
                if($community)
                  {
                  $namePath = $community->getName() . "/" . $namePath;  
                  }
                else
                  {
                  $namePath = $parent->getName() . "/" . $namePath;
                  }
                $root = $parent;
                $parent = $parent->getParent();
                }
              $currentMultiItem['default'] = array('folder_path' => $namePath, 'folder_id' => $defaultFolderId, 'item_ids' => $properties['default']['items']);
              }
            // then get the items and add in their names
            // also need their ids
            // pass all their ids to the ui
            // need to get all children items of that folder to populate
            //$defaultItemIds = $properties['default_items'];
            

            $processSteps[$processStepInd++] = $currentMultiItem;
        }
    }
    if(array_key_exists("singleItems", $inputs) && !empty($inputs["singleItems"])) {
        $singleItemStepLabel = $this->getSingleItemDisplayLabel();
        $processSteps[$processStepInd++] = array('title'=>$singleItemStepLabel, 'type' => 'singleItems');
    }
    if(array_key_exists("parameters", $inputs)) {
        $parameterStepLabel = $this->getParametersDisplayLabel();
        $processSteps[$processStepInd++] = array('title'=> $parameterStepLabel, 'type' => 'parameters');
    }
    
    $this->view->processSteps = $processSteps;
    $this->view->inputs = $inputs;
    $this->view->json['inputs'] = $inputs;
    $this->view->json['processSteps'] = $processSteps;
    $this->renderScript('pipeline/init.phtml');
    }


  /**
   * start a job, via an ajax call.
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
    
    $singleBitstreamItemIds = array();

    // process the input params
    $inputParams = $this->_getAllParams();

    $pipelinePrefix = $this->getPipelinePrefix();
    
    


    
    $configInputs = array();
    $substrInd = strlen($pipelinePrefix);
    $caseFolderPrefix = $pipelinePrefix . "casefolder_";
    $caseFolderSuffix = $pipelinePrefix . "cases_suffix";
    $caseFolderMulticheckPrefix = $pipelinePrefix . "cases_multicheck_";
    $caseFolderMulticheckSubstrInd = strlen($caseFolderMulticheckPrefix);
    $caseFolderSubstrInd = strlen($caseFolderPrefix);
    $multiitemPrefix = $pipelinePrefix . "multiitem_";
    $multiitemSubstrInd = strlen($multiitemPrefix);
    $caseFolders = array();
    $caseSuffixes = array();
    $casesMultichecks = array();
    $multiitems = array();
    foreach($inputParams as $inputParam => $value)
      {
      if(strpos($inputParam, $pipelinePrefix) === 0)
        {
        if(strpos($inputParam, $caseFolderPrefix) === 0)
          {
          // get the case folders by id
          $folderId = substr($inputParam, $caseFolderSubstrInd);
          $caseFolders[] = $folderId;
          }
        else if(strpos($inputParam, $caseFolderSuffix) === 0)
          {
          // split off the last part of the id, this is the variable name
          // add this to the set of suffixes
          $varName = substr($inputParam, $caseFolderSubstrInd+2);
          $caseSuffixes[$varName] = $value;
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
        else if(strpos($inputParam, $caseFolderMulticheckPrefix) === 0)
          {
          // because we lose '.', they get translated to '_', use the value
          // since we are passing the id back in the value as well
          $multicheckVarAndSuffix = substr($value, $caseFolderMulticheckSubstrInd);
          // find the first _ to separate the var name from the suffix
          $firstUnderscoreInd = strpos($multicheckVarAndSuffix, "_");
          $suffix = substr($multicheckVarAndSuffix, $firstUnderscoreInd+1);
          $varname = substr($multicheckVarAndSuffix, 0, $firstUnderscoreInd);
          if(!array_key_exists($varname, $casesMultichecks)) 
            {
            $casesMultichecks[$varname] = array();  
            }
          $casesMultichecks[$varname][] = $suffix;
          }          
        else
          {
          // upper case boolean values for BatchMake
          $lowerValue = strtolower($value);
          if($lowerValue === 'true')
            {
            $value = "TRUE";
            }
          if($lowerValue === 'false')
            {
            $value = "FALSE";
            }

            
            
          // collect all config inputs
          $configInputs[substr($inputParam, $substrInd)] = $value;
          // find the items needed to export
          $paramName = substr($inputParam, $substrInd);
          if(array_key_exists($paramName, $this->getSingleBitstreamItemParams()))
            {
            $singleBitstreamItemIds[$paramName] = $value;
            }
          }
        }
      }

    // create a mapping of variable name to input folder, for the suffix properties
    $inputFolders = $this->getInputFolder();
    $varToFolder = array();
    foreach($inputFolders as $inputFolder => $variables)
      {
      foreach($variables as $variable)
        {
        foreach($variable as $property => $propVal)
          {
          if($property === "varname")
            {
            $varToFolder[$propVal] = $inputFolder;  
            }
          }
        }
      }
      
    // create a mapping of variable name to input folder, for the suffix properties that are connected
    $connectedInputFolders = $this->getInputFolderConnectedDropdowns();
    foreach($connectedInputFolders as $initialVarname => $properties)
      {
      $connectedVars = $properties['connected'];
      foreach($connectedVars as $label => $connectedProperties)
        {
        $varname = $connectedProperties['varname'];
        if(array_key_exists($varname, $caseSuffixes))
          {
          $varToFolder[$varname] = $connectedProperties['subFolder'];  
          }
        }
      }
 
    // create a mapping of variable name to input folder for the multiselects
    $inputFolderMultiselect = $this->getInputFolderMultiselects();
    foreach($inputFolderMultiselect as $inputFolder => $rows)
      {
      foreach($rows as $properties)
        {
        $varname = $properties['varname'];
        $varToFolder[$varname] = $inputFolder;  
        }
      }
      
    foreach($casesMultichecks as $variable => $suffixes)
      {
      // export all selected suffixes in a single variable
      // if the variable was named inputs would want a line like
      // Set(inputs_suffixes '_dti_f_reg.nrrd' '_dti_f.nrrd')
      $configInputs[$variable . "_suffixes"] = $suffixes;
      // specific export for the multiselected, for cases chosen, for each suffix property
      $inputFolder = $varToFolder[$variable];  
      //inputs 'fullpathto_case1_suffix1' 'fullpathto_case1_suffix2' 'fullpathto_case2_suffix1' 'fullpathto_case2_suffix2')
      $executeComponent->exportCasesMultiselects($userDao, $taskDao, $configInputs, $caseFolders, $variable, $suffixes, $inputFolder);
      }

    // specific export for the cases chosen, for each suffix property
    foreach($caseSuffixes as $varName => $suffix)
      {
      if($suffix !== '')
        {
        $inputFolder = $varToFolder[$varName];  
        $executeComponent->exportCases($userDao, $taskDao, $configInputs, $caseFolders, $varName, $suffix, $inputFolder);
        }
      else
        {
        // pass a blank string for this variable
        $configInputs[$varName] = '';
        }
      }
      
      

    // specific export for the multiitems chosen
    $executeComponent->exportMultiitems($userDao, $taskDao, $configInputs, $multiitems);  

  
    // export remaining inputs  
    $configParamsToBitstreamPaths = $executeComponent->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $singleBitstreamItemIds);
    
    // replace any exported item config params with their path values
    foreach($configParamsToBitstreamPaths as $configInput => $bitstreamPath)
      {
      $configInputs[$configInput] = $bitstreamPath;
      }
    
 
    // create output folders
      
    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');

    $outputFolders = $this->getOutputFolderStem(); 
    foreach($outputFolders as $outputFolder)
      {
      $outputFolderType = $outputFolder["output_folder_type"];
      $outputFolderStem = $outputFolder["name"];

      if($outputFolderType == "cases_sibling")
        {
        // get the parent folder of the case folder
        // create a sibling output folder there
        $caseFolder = $folderModel->load($inputParams['casesFolderId']);
        $caseParentId = $caseFolder->getParentId();
        $outputFolderDao = $folderModel->createFolder($outputFolderStem . '-' . $taskDao->getKey(), '', $caseParentId);
        $configInputs['sibling_outputFolderId'] = $outputFolderDao->getFolderId();

        if(array_key_exists("redirect", $outputFolder))
          {
          $methodOutputFolderId = $outputFolderDao->getFolderId();
          }
        }
      else if($outputFolderType === "cases_child")
        {
        $outputFolderIds = array();
        foreach($configInputs['caseFolderIds'] as $caseFolderId)
          {
          $outputFolderDao = $folderModel->createFolder($outputFolderStem . '-' . $taskDao->getKey(), '', $caseFolderId);
          $outputFolderIds[] = $outputFolderDao->getFolderId();
          }
        $configInputs['cases_outputFolderIds'] = $outputFolderIds;  
        if(array_key_exists("redirect", $outputFolder))
          {
          $methodOutputFolderId = $inputParams['casesFolderId'];
          }
        }
      }
        
      
    $condorPostScriptPath = $this->getPostscriptPath();
    $configScriptStem = $this->getConfigScriptStem();
    $bmScript = $this->getBmScript();
    $executeComponent->executeScript($taskDao, $userDao, $condorPostScriptPath, $configScriptStem, $bmScript, $configInputs);
    echo JsonComponent::encode(array('output_folder_id' => $methodOutputFolderId));
    }
  
  
  


}//end class
