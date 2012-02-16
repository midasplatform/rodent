<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php
/** Rodent_ExecuteComponent */
class Rodent_ExecuteComponent extends AppComponent
  {

  public function generatePythonConfigParams($taskDao, $userDao)
    {
    // generate an config file for this run
    $configs = array();
    $midasPath = Zend_Registry::get('webroot');
    $configs[] = 'url http://' . $_SERVER['HTTP_HOST'] . $midasPath ;
    $configs[] = 'appname Default';

    $email = $userDao->getEmail();
    // get an api key for this user
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiDao = $userApiModel->getByAppAndUser('Default', $userDao);
    if(!$userApiDao)
      {
      throw new Zend_Exception('You need to create a web-api key for this user for application: Default');
      }
    $configs[] = 'email '.$email;
    $configs[] = 'apikey '.$userApiDao->getApikey();
    $filepath = $taskDao->getWorkDir() . '/' . 'config.cfg';

    if(!file_put_contents($filepath, implode("\n",$configs)))
      {
      throw new Zend_Exception('Unable to write configuration file: '.$filepath);
      }
    }

//      cfg_aValue
 //     cfg_pValue
 //     cfg_inputImagePath
 //     cfg_outputFolderId
  public function generateBatchmakeConfig($taskDao, $appTaskConfigProperties, $condorPostScriptPath, $configScriptStem)
    {
    $configFileLines = array();
      
    foreach($appTaskConfigProperties as $varName => $varValue)
      {
      $configFileLines[] = "Set(" . $varName . " '" . $varValue . "')";
      }
    $configFileLines[] = "Set(cfg_condorpostscript '" . $condorPostScriptPath . "')";
    $configFileLines[] = "Set(cfg_output_directory '" . $taskDao->getWorkDir() . "')";
    $configFileLines[] = "Set(cfg_exe '/usr/bin/python')";
    $configFileLines[] = "Set(cfg_condordagpostscript '" . BASE_PATH . "/modules/batchmake/library/condor_dag_postscript.py')";
    $configFileLines[] = "Set(cfg_taskId '" . $taskDao->getBatchmakeTaskId() . "')";

    $configFilePath = $taskDao->getWorkDir() . "/" . $configScriptStem . ".config.bms";
    if(!file_put_contents($configFilePath, implode("\n", $configFileLines)))
      {
      throw new Zend_Exception('Unable to write configuration file: '.$configFilePath);
      }
    }



  public function runDemo($userDao, $inputFolderId, $outputFolderId, $seedpointsItemrevisionId)
    {

    $componentLoader = new MIDAS_ComponentLoader();
    $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
    $taskDao = $kwbatchmakeComponent->createTask($userDao);


    // create a Run
    $modelLoad = new MIDAS_ModelLoader();
    $runModel = $modelLoad->loadModel('Run', 'qibench');
    $runModel->loadDaoClass('RunDao', 'qibench');
    $runDao = new Qibench_RunDao();
    $runDao->setBatchmakeTaskId($taskDao->getBatchmakeTaskId());
    $runDao->setSeedpointsItemrevisionId($seedpointsItemrevisionId);
    $runDao->setInputFolderId($inputFolderId);
    $runDao->setOutputFolderId($outputFolderId);
    $runDao->setExecutableName('lstk');
    $runModel->save($runDao);
    // now that we have created a run, create a new folder for this run under
    // the outputFolder
    $folderModel = $modelLoad->loadModel('Folder');
    $outputFolderDao = $folderModel->createFolder('Run ' . $runDao->getKey() . ' Output', '', $outputFolderId);
    // now set the outputFolderId to be the newly created one
    $outputFolderId = $outputFolderDao->getKey();


    // TODO do data export
    // HACK for now hardcode
    // export input collection

    $outputItemStem = "qibench";
    $this->generatePythonConfigParams($taskDao, $userDao);
    list($jobs, $jobsItems) = $this->generateJobs($inputFolderId, $userDao, $seedpointsItemrevisionId);//

    // export the items to the work dir data dir
    $datapath = $taskDao->getWorkDir() . '/' . 'data';
    //echo "datapath[$datapath]";
    if(!KWUtils::mkDir($datapath))
      {
      throw new Zend_Exception("couldn't create data export dir: ". $datapath);
      }
    $exportComponent = $componentLoader->loadComponent('Export');


    $jobsItemsIds = array();
    foreach($jobsItems as $jobItemDao)
      {
      // use the item id as both key and value so we don't end up exporting duplicates
      $jobsItemsIds[$jobItemDao->getKey()] = $jobItemDao->getKey();
      }
    $exportComponent->exportBitstreams($userDao, $datapath, $jobsItemsIds, true);


    // need a mapping of item name to item id
    $jobConfigParams = $this->generateBatchmakeConfig($taskDao, $runDao, $datapath, $jobs, $jobsItems, $outputFolderId);


    $bmScript = "LesionSegmentationQIBench.bms";
    $kwbatchmakeComponent->preparePipelineScripts($taskDao->getWorkDir(), $bmScript);

    $kwbatchmakeComponent->preparePipelineBmms($taskDao->getWorkDir(), array($bmScript));

    //$kwbatchmakeComponent->compileBatchMakeScript($taskDao->getWorkDir(), $bmScript);
    $dagScript = $kwbatchmakeComponent->generateCondorDag($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);

    /*
//when i uncomment either of these two lines, even though they work, the
//view breaks


// this line is commented out just to not take so much time/cpu, it submits to condor
         //$kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);
*/
    return array($runDao, $jobConfigParams);
    }






} // end class
?>