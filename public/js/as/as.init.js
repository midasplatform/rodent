var midas = midas || {};
midas.rodent = midas.rodent || {};
midas.rodent.as = midas.rodent.as || {};

midas.rodent.as.currentBrowser = false;
//var inittializedExecutableForm = false;
//var executableValid = false;
//var isExecutableMeta = false;
//var isDefineAjax = true;
var results = new Array;

$(document).ready(function(){
  // Initialize Smart Wizard
  $('#wizard').smartWizard(
  {
  // Properties
    keyNavigation: true, // Enable/Disable key navigation(left and right keys are used if enabled)
    enableAllSteps: false,  // Enable/Disable all steps on first load
    transitionEffect: 'fade', // Effect on navigation, none/fade/slide/slideleft
    contentURL:null, // specifying content url enables ajax content loading
    contentCache:false, // cache step contents, if false content is fetched always from ajax url
    cycleSteps: false, // cycle step navigation
    enableFinishButton: false, // makes finish button enabled always
    errorSteps:[],    // array of step numbers to highlighting as error steps
    labelNext:'Next', // label for Next button
    labelPrevious:'Previous', // label for Previous button
    labelFinish:'Create Job',  // label for Finish button
    // Events
    onLeaveStep: midas.rodent.as.onLeaveStepCallback, // triggers when leaving a step
    onShowStep: midas.rodent.as.onShowStepCallback,  // triggers when showing a step
    onFinish: midas.rodent.as.onFinishCallback  // triggers when Finish button is clicked
  }
  );


  $('#wizard').show();

});






midas.rodent.as.onLeaveStepCallback = function(obj)
  {
  var step_num= obj.attr('rel'); // get the current step number
  return midas.rodent.as.validateSteps(step_num); // return false to stay on step and true to continue navigation
  }

midas.rodent.as.onFinishCallback = function()
  {
   if(midas.rodent.as.validateAllSteps())
     {
     requestData = {};
     $.each($(".pipelineparameter"), function(index, input) {
         if(input.type === "checkbox") {
             requestData[input.id] = input.checked;
         }
         else {
             requestData[input.id] = input.value;
         }
     });
     $(this).after('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Saving..." />')
     $(this).remove();
     $.ajax(
       {
       type: "POST",
       url: json.global.webroot+"/rodent/as/startjob",
       data: requestData,
       success: function(data, textStatus)
         {
         console.log(data);
        
         //window.location.replace($('.webroot').val()+'/remoteprocessing/job/manage')
         },
       error: function(xhr, textStatus, errorThrown) {
           console.log("error");
       },
       complete: function(xhr, textStatus) {
           console.log("complete");
       }
       
       });
     }
   else
     {
     createNotive("There are some errors.", 4000);
     }
  }


midas.rodent.as.validateSteps = function(stepnumber)
  {
  var isStepValid = true;
  // validate step 1
  //HACK for now, no validation
  return true;
  }

midas.rodent.as.validateAllSteps = function()
  {
  return midas.rodent.as.validateSteps(1) && 
         midas.rodent.as.validateSteps(2) && 
         midas.rodent.as.validateSteps(3) && 
         midas.rodent.as.validateSteps(4);
  }

midas.rodent.as.onShowStepCallback = function(obj)
  {
  var step_num = obj.attr('rel'); // get the current step number
  /*if(step_num == 1)
    {
    $('#midas_rodent_unu_browseImageFile').click(function(){
      loadDialog("selectitem_imageinput","/browse/selectitem");
      showDialog('Browse');
      currentBrowser = 'imageinput';
    });
    }*/
  if(step_num == 1)
    {
    var prefix = json.inputs.prefix;
    var folders = json.inputs.folders;
    $.each( folders , function(k, v){
      var id = prefix + k;
      $('#'+id+'_button').click(function(){
          loadDialog("selectfolder_outputfolder","/browse/selectfolder");
          showDialog('Browse for Output Directory');
          currentBrowser = id;
      });
    }); 
    }
  if(step_num == 2)
    {
    var prefix = json.inputs.prefix;
    var items = json.inputs.items;
    $.each( items , function(k, v){
      var id = prefix + k;
      $('#'+id+'_button').click(function(){
          loadDialog("selectitem_inputitem","/browse/selectitem");
          var label = json.itemLabels[k];
          showDialog('Browse for '+label);
          currentBrowser = id;
      });
    }); 
    }


  }




itemSelectionCallback = function(name, id)
  {
  $('#'+currentBrowser+'_name').html(name);
  $('#'+currentBrowser).val(id);
  return;
  }

folderSelectionCallback = function(name, id)
  {
  $('#'+currentBrowser+'_name').html(name);
  $('#'+currentBrowser).val(id);
  return;
  }
