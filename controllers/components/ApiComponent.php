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

/** Component for api methods */
class Rodent_ApiComponent extends AppComponent
{


  /**
   * Pass the args and a list of required parameters.
   * Will throw an exception if a required one is missing.
   */
  private function _validateParams($args, $requiredList)
    {
    foreach($requiredList as $param)
      {
      if(!array_key_exists($param, $args))
        {
        throw new Exception('Parameter '.$param.' is not defined', MIDAS_INVALID_PARAMETER);
        }
      }
    }

  /** Return the user dao */
  private function _getUser($args)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    return $authComponent->getUser($args,  Zend_Registry::get('userSession')->Dao);
    }


  /**
   * list all of the available suffixes for a set of case folders,
   * for a given subfolder name.
   * @param folder_id
   * @param selected_subfolder_name
   * @return array of suffixes
   */
  function listCaseSuffixes($args)
    {
    $this->_validateParams($args, array('folder_id', 'selected_subfolder_name'));
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('Anonymous users may not list case suffixes');
      }
    $folderId = $args['folder_id'];
    $selectedSubfolderName = $args['selected_subfolder_name'];

    $modelLoad = new MIDAS_ModelLoader();
    $folderModel = $modelLoad->loadModel('Folder');
    $casesRoot = $folderModel->load($folderId);
    // get the children folders of the folderId
    // take any one of them as they should all have the same structure
    // so the first is fine
    $cases = $folderModel->getChildrenFoldersFiltered($casesRoot, $userDao, MIDAS_POLICY_READ);
    $caseZero = $cases[0];
    $caseZeroName = $caseZero->getName();
    $caseZeroNameLength = strlen($caseZeroName);
    $caseZeroSubfolders = $folderModel->getChildrenFoldersFiltered($caseZero, $userDao, MIDAS_POLICY_READ);
    // then look in the subfolder of this case named selectedSubfolderName
    $suffixes = array();
    foreach($caseZeroSubfolders as $subfolder)
      {
      if($subfolder->getName() === $selectedSubfolderName)
        {
        // in this folder, list all items
        $subfolderItems = $folderModel->getItemsFiltered($subfolder, $userDao, MIDAS_POLICY_READ);
        foreach($subfolderItems as $subfolderItem)
          {
          $subfolderItemName = $subfolderItem->getName();  
          if(strpos($subfolderItemName, $caseZeroName) === 0)
            {
            // find all items that match the name of the case
            // so these items are like $case . $suffix
            // add $suffix to the list of $suffixes
            $suffix = substr($subfolderItemName, $caseZeroNameLength);
            $suffixes[] = $suffix;
            }
          }
        }  
      }
      return array('suffixes' => $suffixes);
    }





} // end class