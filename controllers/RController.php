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
/** r controller uses a condor postscript because it's really close and that marion is rushing ;)*/
class Rodent_RController extends Rodent_PipelineController
{

  function getPipelinePrefix() { return "rodent_registration_"; }
  function getUiTitle() { return "Registration Pipeline Wizard"; }
  function getCasesSelection() { return array('id'=> "casesdirectory", 'label' => "Select the Cases Directory"); }

  function getMultiItemSelections() { return array(); }
                                 
  function getSingleItemSelections() { return 
      array("templatefile" => array("label" => "Template file", "bitstreamCount" => "single")); }
  function getParameters()
    {
    //TODO want to add in default value for parameters
    
    // TODO if time permits, add in a dropdown combo box for inputType 
    // TODO if even more time permits, verify orientation to be some ordered combination
    // of 1 per group of (L/R) (P/A) (S/I), this is low priority
      
    return array("bias" => array("type" => "boolean", "label" => "Correct bias?", "default" => true),
        "skullstrip" => array("type" => "boolean", "label" => "Use skullstrip function?", "default" => true),
        "scaled" => array("type" => "boolean", "label" => "Are the inputs scaled at 1,1,1?"),
        "inputType" => array("type" => "select", "label" => "Input Type", "options" => array("DTI","DWI","scalar")),
        "orientation" => array("type" => "text", "label" => "Manual orientation", "default" => "LPS"));
    }
  function getSingleBitstreamItemParams() { return array("segmentationfile" => "Segmentation file", "templatefile" => "Template file"); }
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/a_condor_postscript.py'; }
  function getConfigScriptStem() { return "r"; }
  function getBmScript() { return "r1.pipeline.bms"; }
  
  
  // TODO make additionalimages and additionalimagesnn, transform and initialtransform optional once we can make them optional
  function getInputFolder() { return array(
      "2-Registration" => array(
          array("label"=> "inputs", "varname" => "casesInputs"),
          array("label"=> "additionalimages", "varname" => "casesAdditionalImages"),
          array("label"=> "additionalimagesnn", "varname" => "casesAdditionalImagesNN"),
          array("label"=> "transform", "varname" => "casesTransforms"),
          array("label"=> "initialtransform", "varname" => "casesInitialTransforms"))); }
  function getOutputFolderStem() { return array(
      array("output_folder_type" => "cases_child", "name" => "2-Registration")); }
  
}//end class  NOTE : it's going to look for the files in the 2-Reg dir in MIDAS, this needs to be changed in the future
