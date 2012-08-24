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
  function getMultiItemSelections() { return array(); }
                                 
  function getSingleItemSelections() { return 
      array("imagegridfile" => array("label" => "Image grid file", "bitstreamCount" => "single")); }
  // TODO move imagegridfile to cases (getInputFolder) once this getSingleItemSelections is allowed to be optional
  // it will go under 2-Registration
      
  function getParameters()
    {
    //TODO want to add in default value for parameters
    return array("average" => array("type" => "boolean", "label" => "Recompute the average?", "default" => true),
        "scalar" => array("type" => "boolean", "label" => "Is the input image a scalar image?"),
        "scaled" => array("type" => "boolean", "label" => "Are the inputs scaled at 1,1,1?"),
        "histogrammatch" => array("type" => "boolean", "label" => "Use histogram match?", "default" => true),
        // TODO Francois will change the bms to hard code this, after he contacts me I'll remove radius
        "radius" => array("type" => "text", "label" => "radius", "default" => "1"));
    }
  function getSingleBitstreamItemParams() { return array("populationaveragefile" => "Population Average File", "segmentationfile" => "Segmentation file", "imagegridfile" => "Image grid file"); }
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/a_condor_postscript.py'; }
  function getConfigScriptStem() { return "a"; }
  function getBmScript() { return "a1.pipeline.bms"; }
  
  // TODO make optional: 
  // TODO Francois will send an email describing originals and dti, there will probably have to be a special case UI component for these
  // put the originals and dti stuff after setting the cases, try to put on the same page if possible b/c these relate to cases
  
  function getInputFolder() { return array(
      "2-Registration" => array(
          array("label"=> "inputs", "varname" => "casesInputs"),
          array("label"=> "originals", "varname" => "casesOriginals"),
          array("label"=> "dti", "varname" => "casesDTIs"),
          array("label"=> "transform", "varname" => "casesTransforms")),
      "3-SkullStripping-a" => array(
          array("label"=> "mask", "varname" => "casesMasks"))); }
  function getOutputFolderStem() { return array(
      array("output_folder_type" => "cases_sibling", "name" => "Average", "redirect" => true),
      array("output_folder_type" => "cases_child", "name" => "4-Average")); }
  
}//end class