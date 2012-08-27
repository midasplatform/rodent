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

  public function generateBatchmakeConfig($taskDao, $appTaskConfigProperties, $condorPostScriptPath, $configScriptStem)
    {
    $configFileLines = array();
      
    foreach($appTaskConfigProperties as $varName => $varValue)
      {
      if(is_array($varValue))
        {
        $configFileLine = "Set(" . $varName;
        foreach($varValue as $jobConfigParamValue)
          {
          $configFileLine .= " '" . $jobConfigParamValue . "'";
          }
        $configFileLine .= ")";
        $configFileLines[] = $configFileLine;
        }
      else
        {
        $configFileLines[] = "Set(" . $varName . " '" . $varValue . "')";
        }
      }
      
    $configFileLines[] = "Set(cfg_condorpostscript '" . $condorPostScriptPath . "')";
    $configFileLines[] = "Set(cfg_output_directory '" . $taskDao->getWorkDir() . "')";
    $configFileLines[] = "Set(cfg_exe '/usr/bin/python26 ')";
    $configFileLines[] = "Set(cfg_condordagpostscript '" . BASE_PATH . "/modules/batchmake/library/condor_dag_postscript.py')";
    $configFileLines[] = "Set(cfg_taskId '" . $taskDao->getBatchmakeTaskId() . "')";

    $configFilePath = $taskDao->getWorkDir() . "/" . $configScriptStem . ".config.bms";
    if(!file_put_contents($configFilePath, implode("\n", $configFileLines)))
      {
      throw new Zend_Exception('Unable to write configuration file: '.$configFilePath);
      }
    }

  public function exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport)
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
    $componentLoader = new MIDAS_ComponentLoader();
    $exportComponent = $componentLoader->loadComponent('Export');
    $exportComponent->exportBitstreams($userDao, $datapath, $itemIds, $symlink);

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
      if($itemDao)
        {
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
      else 
        {
        $configParamsToBitstreamPaths[$configParam] = "";
        }
      }
    return $configParamsToBitstreamPaths;
    }
  
  
  public function exportItemsToWorkDataDir($userDao, $taskDao, $itemIds)
    {
    // export the items to the work dir data dir
    $datapath = $taskDao->getWorkDir() . '/' . 'data';
    if(!KWUtils::mkDir($datapath))
      {
      throw new Zend_Exception("couldn't create data export dir: ". $datapath);
      }
    $componentLoader = new MIDAS_ComponentLoader();
    $exportComponent = $this->$componentLoader->loadComponent('Export');
    $symlink = true;
    $exportComponent->exportBitstreams($userDao, $datapath, $itemIds, $symlink);
    }

  public function exportCases($userDao, $taskDao, &$configInputs, $caseFolders, $varName, $suffix, $selectedSubfolderName)
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
        if($folder->getName() === $selectedSubfolderName)
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
    
    $casesToExportPaths = $this->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport);
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
    $configInputs[$varName] = $caseInputs;
    $configInputs['caseFolderIds'] = $caseFolders;
    $configInputs['caseInds'] = $caseIndices;
    }
    
    
    
    

    // specific export for the multiitems chosen
    public function exportMultiitems($userDao, $taskDao, &$configInputs, $multiitems) 
      {
      $modelLoad = new MIDAS_ModelLoader();
      $itemModel = $modelLoad->loadModel('Folder');
      foreach($multiitems as $paramId => $items)
        {
        $itemsForExport = array();
        foreach($items as $itemId)
          {
          $itemsForExport[$itemId] = $itemId;
          }
        $configInputs[$paramId] = array();
        $itemIdsToExportPaths = $this->exportSingleBitstreamItemsToWorkDataDir($userDao, $taskDao, $itemsForExport);
        foreach($itemIdsToExportPaths as $itemId => $exportPath)
          {
          $configInputs[$paramId][] = $exportPath;
          }
        }
      }
//need to do something with multiitem
//prefix_multiitem_id_itemid
//            combine them into a multiitem, make the multiitem a list, export everything the in the list, export as prefix_id

          
    

  public function executeScript($taskDao, $userDao, $condorPostScriptPath, $configScriptStem, $bmScript, $configInputs)
    {
    // generate and export midas client communication params
    $this->generatePythonConfigParams($taskDao, $userDao);      
    $this->generateBatchmakeConfig($taskDao, $configInputs, $condorPostScriptPath, $configScriptStem);
  
    // export the batchmake scripts
    $componentLoader = new MIDAS_ComponentLoader();
    $kwbatchmakeComponent = $componentLoader->loadComponent('KWBatchmake', 'batchmake');
    $bmScripts = $kwbatchmakeComponent->preparePipelineScripts($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->preparePipelineBmms($taskDao->getWorkDir(), $bmScripts);

    // generate and run the condor dag
//    $kwbatchmakeComponent->compileBatchMakeScript($taskDao->getWorkDir(), $bmScript);
    $dagScript = $kwbatchmakeComponent->generateCondorDag($taskDao->getWorkDir(), $bmScript);
    $kwbatchmakeComponent->condorSubmitDag($taskDao->getWorkDir(), $dagScript);
    }




} // end class
?>
