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
require_once BASE_PATH . '/modules/rodent/controllers/PipelineController.php';

class Rodent_StController extends Rodent_PipelineController
{

  function getPipelinePrefix() { return "rodent_stats_"; }
  function getUiTitle() { return "Stats Pipeline Wizard"; }
  function getCasesSelection() { return array('id'=> "casesdirectory", 'label' => "Select the Cases Directory"); }
  
  function getMultiItemSelections() { return array(); }
                                 
  function getSingleItemSelections() { return array(); }
  function getParameters()
    {
    return array("labelssuffixes" => array("type" => "text", "label" => "Label map suffixes", "default" => "_dti_f_reg_FA.nrrd"));
    }
  function getSingleBitstreamItemParams() { return array(); }
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/a_condor_postscript.py'; }
  function getConfigScriptStem() { return "st"; }
  function getBmScript() { return "st.pipeline.bms"; }
  function getInputFolder() { return array(
      "2-Registration" => array(
          array("label"=> "Image To Analyze", "varname" => "statinputs"))); }
  function getOutputFolderStem() { return array(
      array("output_folder_type" => "cases_child", "name" => "7-Stats")); }
  
}//end class
