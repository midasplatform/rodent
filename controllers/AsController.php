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
class Rodent_AsController extends Rodent_AppController
{
  public $_models = array('Item', 'Bitstream', 'ItemRevision', 'Assetstore', 'Folder');
  public $_components = array('Export');

  protected $pipelinePrefix = "rodent_atlassegmentation_";
  
  /** init a job*/
  function initAction()
    {
    $this->view->header = "Average Segmentation Pipeline Wizard";
    if(!$this->logged)
      {
      $this->haveToBeLogged();
      return false;
      }
    $this->requireAdminPrivileges();
    
    
    $folderSelections = array("outputdirectory" => "Output Directory");
 
    $itemSelections = array("populationaverage" => "Population Average",
        "populationaveragemask" => "Population Average Mask",
        "template" => "Template",
        "templatemask" => "Template Mask",
        "segmentation" => "Segmentation",
        "imagegrid" => "Image Grid");
    
    $parameters = array("diffeomorphic" => array("type" => "boolean", "label" => "Checked for diffeomorphic, Unchecked for elastic transformation?"),
        "smooth" => array("type" => "boolean", "label" => "Use smooth option?"));
    
    $inputs = array("prefix" => $this->pipelinePrefix, "folders" => $folderSelections, "items" => $itemSelections, "parameters" => $parameters);
    $this->view->inputs = $inputs;
    $this->view->json['inputs'] = $inputs;
    $this->view->json['itemLabels'] = $itemSelections;
    
    
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
    $executeComponent = $componentLoader->loadComponent('Execute', 'rodent');
    $taskDao = $kwbatchmakeComponent->createTask($userDao);
    
    // export any data needed by the pipeline from midas
    $singleBitstreamItemParams = array("populationaverage" => "populationaverage",
                                       "populationaveragemask" => "populationaveragemask",
                                       "template"=>"template",
                                       "templatemask"=>"templatemask",
                                       "segmentation" => "segmentation",
                                       "imagegrid" => "imagegrid");
    
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
    
    $configParamsToBitstreamPaths = $executeComponent->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $singleBitstreamItemIds);

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
    $outputFolderDao = $folderModel->createFolder('AS task ' . $taskDao->getKey() . ' Output', '', $outputFolderId);
    // now set the outputFolderId to be the newly created one
    $outputFolderId = $outputFolderDao->getKey();
    // set the newly created child folder id   
    $configInputs['outputdirectory'] = $outputFolderId;


    
        
    $condorPostScriptPath = BASE_PATH . '/modules/rodent/library/py/as_condor_postscript.py';
    $configScriptStem = "as";
    $bmScript = "as.pipeline.bms";
    $executeComponent->executeScript($taskDao, $userDao, $condorPostScriptPath, $configScriptStem, $bmScript, $configInputs);

    
//    executeScript($taskDao, $userDao, $condorPostScriptPath, $configScriptStem, $bmScript, $configInputs)
// -->>>>>>    $taskDao, $userDao, condorPostScriptPath, $configScriptStem,$bmScript,$configInputs
    /*
    // generate and export midas client communication params
    $executeComponent = $componentLoader->loadComponent('Execute', 'rodent');
    $executeComponent->generatePythonConfigParams($taskDao, $userDao);      

    
    $condorPostScriptPath = BASE_PATH . '/modules/rodent/library/py/as_condor_postscript.py';
    $configScriptStem = "as";

    $executeComponent->generateBatchmakeConfig($taskDao, $configInputs, $condorPostScriptPath, $configScriptStem);
  
    // export the batchmake scripts
    $bmScript = "as.pipeline.bms";
    $kwbatchmakeComponent->preparePipelineScripts($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->preparePipelineBmms($taskDao->getWorkDir(), array($bmScript));

    // generate and run the condor dag
    $kwbatchmakeComponent->compileBatchMakeScript($taskDao->getWorkDir(), $bmScript);
    $dagScript = $kwbatchmakeComponent->generateCondorDag($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);
*/
  }
    
  
  



}//end class
