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
/** w controller uses a condor postscript because it's really close and that marion is rushing ;)*/
class Rodent_WController extends Rodent_PipelineController
{

  function getPipelinePrefix() { return "rodent_warping_"; }
  function getUiTitle() { return "Warping Pipeline Wizard"; }
  function getCasesSelection() { return array('id'=> "casesdirectory", 'label' => "Select the Cases Directory"); }
//  function getMultiItemSelections() { return array("templatefiles" => "Template Files"); }
// want all this next stuff to have a default
  
  function getMultiItemSelections() { return array("labelmapsfiles" => "Label Map Files"); }
                                 
  function getSingleItemSelections() { return 
      array("templatefile" => array("label" => "Template file", "bitstreamCount" => "single")
            "templatemaskfile" => array("label" => "Template Mask file", "bitstreamCount" => "single")); }
  function getParameters()
    {
    //TODO want to add in default value for parameters
    return array("usinghfield" => array("type" => "boolean", "label" => "Using inverse H-Field?", "default" => true));
    }
  function getSingleBitstreamItemParams() { return array("templatefile" => "Template file", "templatemaskfile" => "Template Mask file"); }
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/a_condor_postscript.py'; }
  function getConfigScriptStem() { return "w"; }
  function getBmScript() { return "w.pipeline.bms"; }
  function getInputFolder() { return array(
      "3-SkullStripping-a" => array(
          array("label"=> "Masks", "varname" => "casesMasks")),
      "4-AtlasCreation" => array(
          array("label"=> "Inverse H-Fields", "varname" => "casesHFields"))); }
  function getOutputFolderStem() { return array(
      array("output_folder_type" => "cases_child", "name" => "6-Warping")); }
  
}//end class  NOTE : it's going to look for the files in the 2-Reg dir in MIDAS, this needs to be changed in the future
