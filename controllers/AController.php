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
/** a controller*/
class Rodent_AController extends Rodent_PipelineController
{

  function getPipelinePrefix() { return "rodent_atlas_"; }
  function getUiTitle() { return "Average Pipeline Wizard"; }
  function getCasesSelection() { return array('id'=> "casesdirectory", 'label' => "Select the Cases Directory"); }
//  function getMultiItemSelections() { return array("templatefiles" => "Template Files"); }
// want all this next stuff to have a default
  
  function getMultiItemSelections() { return array(); }
                                 
  function getSingleItemSelections() { return 
      array("populationaveragefile" => array("label" => "Population Average File", "bitstreamCount" => "single"),
            "segmentationfile" => array("label" => "Segmentation file", "bitstreamCount" => "single"),
            "imagegridfile" => array("label" => "Image grid file", "bitstreamCount" => "single")); }
  function getParameters()
    {
    //TODO want to add in default value for parameters
    return array("average" => array("type" => "boolean", "label" => "Recompute the average?", "default" => true),
        "scalar" => array("type" => "boolean", "label" => "Is the input image a scalar image?"),
        "scaled" => array("type" => "boolean", "label" => "Are the inputs scaled at 1,1,1?"),
        "histogrammatch" => array("type" => "boolean", "label" => "Use histogram match?", "default" => true),
        "radius" => array("type" => "text", "label" => "radius", "default" => "1"));
    }
  function getSingleBitstreamItemParams() { return array("populationaveragefile" => "Population Average File", "segmentationfile" => "Segmentation file", "imagegridfile" => "Image grid file"); }
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/a_condor_postscript.py'; }
  function getConfigScriptStem() { return "a"; }
  function getBmScript() { return "a1.pipeline.bms"; }
  function getInputFolder() { return array(
      "2-Registration" => array(
          array("label"=> "inputs", "varname" => "casesInputs"),
          array("label"=> "originals", "varname" => "casesOriginals"),
          array("label"=> "dti", "varname" => "casesDTIs"),
          array("label"=> "transform", "varname" => "casesTransforms")),
      "3-SkullStripping-a" => array(
          array("label"=> "mask", "varname" => "casesMasks"))); }
  function getOutputFolderStem() { return array("output_folder_type" => "cases_sibling", "name" => "Average"); }
  
}//end class