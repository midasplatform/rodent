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
/** as controller*/
class Rodent_SsController extends Rodent_PipelineController
{

  function getPipelinePrefix() { return "rodent_skullstrip_"; }
  function getUiTitle() { return "Skull Strip Pipeline Wizard"; }
  function getCasesSelection() { return array('id'=> "casesdirectory", 'label' => "Select the Cases Directory"); }
  function getMultiItemSelections() { return array("templatefiles" => "Template Files"); }
// want all this next stuff to have a default
  
//function getMultiItemSelections() { return array("templatefiles" => array("label" =>"Template files", "default_folder" => "569", "default_items" => array("1391", "1392"))); }

  // also for this next one, want a checkbox selection that turns it on if they check
  function getSingleItemSelections() { return array("templategridfile" => array("label" => "Template grid file", "bitstreamCount" => "single")); }
  function getParameters()
    {
    //TODO want to add in default value for parameters
    return array("newmasktag" => array("type" => "text", "label" => "Tag of the mask that is gonna be created"),
	"rigid" => array("type" => "boolean", "label" => "Using rigid transformation? (usually checked)"),
        "registration" => array("type" => "boolean", "label" => "Do registration? (usually checked)"),
        "biasfieldcorrection" => array("type" => "boolean", "label" => "Use bias field correction?"),
        "rigidisFA" => array("type" => "boolean", "label" => "rigidisFA"),
        "scalar" => array("type" => "boolean", "label" => "Is the input scalar?"),
        "scaled" => array("type" => "boolean", "label" => "Is the input scaled?"),
        "filtercurvature" => array("type" => "boolean", "label" => "filtercurvature? (usually checked)"),
        "segimagestype" => array("type" => "text", "label" => "Suffix of the images used for segmentation (usually the same suffix given on folder window)"),
        "radius" => array("type" => "integer", "label" => "radius (usually 5)"),
        "abcpriors" => array("type" => "text", "label" => "abcpriors (usually 1 1 1 1)"),
        "rigidisMD" => array("type" => "boolean", "label" => "rigidisMD"),
        "sequence" => array("type"=>"text", "label"=>"sequence 0 NB_LOOPS 1 (usually 0 0 1)"));
    }
  function getSingleBitstreamItemParams() { return array("templategridfile" => "Template grid file"); }
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/ss_condor_postscript.py'; }
  function getConfigScriptStem() { return "ss"; }
  function getBmScript() { return "ss1.pipeline.bms"; }
  function getInputFolder() { return "2-Registration"; }
  function getOutputFolderStem() { return "3-SkullStripping"; }
  
  
    

    
  
  



}//end class
