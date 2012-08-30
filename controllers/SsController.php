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

  // a unique string for the pipeline, something like rodent_pipelinename_
  function getPipelinePrefix() { return "rodent_skullstrip_"; }
  // title to display on UI wizard
  function getUiTitle() { return "Skull Strip Pipeline Wizard"; }
  
  // return an array if you want the user to be able to select cases for this pipeline
  // id will be a batchmake config variable, i.e. for this pipeline, would go in
  // ss.config.bms, and would create to a line in that file like:
  // Set(casesdirectory '455')
  // where 455 is the midas ID for the folder chosen where all of the case folders
  // are nested under
  function getCasesSelection() { return array('id'=> "casesdirectory", 'label' => "Select the Cases Directory"); }
  
  // under the cases selection in the UI, for every element listed in this
  // getInputFolder method, the UI will present one row with a drop down
  // for user selection
  // the variable name for that row/dropdown as it flows into the ss.config.bms
  // will be "varname" in this getInputFolder array
  // this "varname" value, e.g. casesInputs, will be a list with one member for every
  // case you have checked, so there will be a line in ss.config.bms like:
  // Set(casesInputs '/midas/midas3base/batchmake/tmp/SSP/2/99//data/333/0002_dti_f_reg.nrrd' '/midas/midas3base/batchmake/tmp/SSP/2/99//data/418/0003_dti_f_reg.nrrd')
  // assuming we have chosen 2 cases
  
  // these rows in the getInputFolder array will nest under keys like:
  // 2-Registration
  // these keys tell Midas to look into the subfolders inside the case
  // folders with this name, to look for all of the items there,
  // and to create a list of suffixes to populate the dropdown with.
  
  // so for this example, we will have one row with a dropdown,
  // that dropdown will be populated by suffixes in the 2-Registration
  // subfolder of the first case, and whatever value the user chooses
  // for the suffix, the file corresponding to that will be chosen from
  // the 2-Registration folder and have its path populated in the casesInputs
  // batchmake config variable, the particular file will be a combination of the
  // case name defined by the folder name, and the suffix
  // for the example listed above, the user
  // would have chosen the _dti_f_reg.nrrd
 
  // if a row should be optional (allow a blank to flow to the batchmake variable),
  // add in the "optional" => "true" key-value
  function getInputFolder() { return array(
      "2-Registration" => array(
          array("label"=> "Inputs used for registration", "varname" => "casesInputs"),
      //TODO    
      // we have recently added this here, moved it from parameters, but the
      // batchmake script needs to be updated to deal with this change
          array("label"=> "Inputs used for segmentation (if none, suffix above will be used)", "varname" => "segimagestype", "optional" => "true")
          )); }
  //TODO
  // we'd like to have another variable here that would be templategridfile, that should be optional
  // to do this, we need to 
  // 1) make these optional--DONE
  // 2) make the singleitemselections optional--DONE
  // 3) change the batchmake pipeline for ss to use a list rather than a single value
  // 4) permanently remove the parameter scaled
  
  
  // this will create a UI page that allows the user to select a folder in midas
  // then that folder is interrogated and every item in that folder is presented
  // with a checkbox
  // the array keys here, in this example "templatefiles" will be the
  // batchmake config variable, and all of the selected items will have their
  // full paths be the values passed in, e.g.
  //Set(templatefiles '/midas/midas3base/batchmake/tmp/SSP/2/99//data/1840/1.mha' '/midas/midas3base/batchmake/tmp/SSP/2/99//data/1844/2.mha' '/midas/midas3base/batchmake/tmp/SSP/2/99//data/1848/3.mha' '/midas/midas3base/batchmake/tmp/SSP/2/99//data/1852/4.mha' '/midas/midas3base/batchmake/tmp/SSP/2/99//data/1858/template.mha')
 
  // if this step is optional, return an empty array, i.e. 
  // return array();
   
  // this step can also have multiple of these defined, e.g. : 
  //function getMultiItemSelections() { return array("templatefiles" => array('label' => 'Template & Probability Map Files'), "templatefiles2" => array('label' => '2Template & Probability Map Files')); }
  function getMultiItemSelections() { return array("templatefiles" => array('label' => 'Template & Probability Map Files')); }

  // this method will create a UI step that allows the user to select a single
  // individual midas item for each row, where the array key will be the batchmake config variable
  // e.g., here the variable would be templategridfile
  function getSingleItemSelections() { return array("templategridfile" => array("label" => "Template grid file", "bitstreamCount" => "single")); }
  function getSingleItemDisplayLabel() { return "Select Template Grid File"; }
  
  
  
  // each array key, e.g. newmasktag will be the batchmake config variable
  // the types should be boolean or text or ????, there can be a default, which will be
  // autopopulated in the UI
  function getParameters()
    {
    return array("newmasktag" => array("type" => "text", "label" => "Tag of the mask that will be created", 'default' => 'mask'),
        "registration" => array("type" => "boolean", "label" => "Perform registration", 'default' => true),
        "rigid" => array("type" => "boolean", "label" => "Perform rigid registration", 'default' => true),
        "biasfieldcorrection" => array("type" => "boolean", "label" => "Perform bias field correction"),
        "rigidisFA" => array("type" => "boolean", "label" => "Registration image is FA"),
        "rigidisMD" => array("type" => "boolean", "label" => "Registration image is MD"),
        "scalar" => array("type" => "boolean", "label" => "Original input image is scalar"),
        "filtercurvature" => array("type" => "boolean", "label" => "Filter curvature (Otherwise Gradient anisotropic diffusion is used)", 'default' => true),
        "radius" => array("type" => "text", "label" => "radius", 'default' => '5'),
        "abcpriors" => array("type" => "text", "label" => "Probability maps priors", 'default' => '1 1 1 1'));
    }
    
  // states that the templategridfile has only one bitstream, used for exporting to the filesystem 
  function getSingleBitstreamItemParams() { return array("templategridfile" => "Template grid file"); }
  
  // this is the path to the python script that will be run
  function getPostscriptPath() { return BASE_PATH . '/modules/rodent/library/py/ss_condor_postscript.py'; }

  // this is the string that will be used to create the batchmake config file
  // so here, the midas variables will output to ss.config.bms
  // this string ss.config.bms should be included in whatever is the root bms
  // which is defined by getBmScript
  function getConfigScriptStem() { return "ss"; }
  // the name of the root bms file, which should include any other bms files used
  // and should include the batchmake config file
  function getBmScript() { return "ss1.pipeline.bms"; }
  
  // an array of output folders, these will be created in Midas
  // if the output_folder_type of cases_child will be created, like here
  // then a new folder will be created under each case folder with the name
  
  // if an output_folder_type of cases_sibling exists (see AController.php), then a new folder that
  // is at the same level as the cases root folder will be created with the name given,
  // if redirect = true is set for the row (cases_sibling or cases_child)
  // then after starting the pipeline
  // the Midas view will redirect to that new folder
  
  // all of the folders created by this run of the pipeline will be suffixed with
  // an index from a count of all batchmake jobs that have been run
  
  // e.g. for the 121st batchmake run, if case 0001 has been chosen, a new folder will be created:
  
  // cases
  //   0001
  //       3-SkullStripping-121
  
  function getOutputFolderStem() { return array(
      array("output_folder_type" => "cases_child", "name" => "3-SkullStripping")); }
      
      
      
      
      
}//end class
