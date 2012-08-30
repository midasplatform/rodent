var midas = midas || {};
midas.rodent = midas.rodent || {};
midas.rodent.pipeline = midas.rodent.pipeline|| {};

midas.rodent.pipeline.currentBrowser = false;
var results = new Array;

$(document).ready(function(){
  // Initialize Smart Wizard
  $('#wizard').smartWizard(
  {
  // Properties
    keyNavigation: false, // Disable key navigation
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
    onLeaveStep: midas.rodent.pipeline.onLeaveStepCallback, // triggers when leaving a step
    onShowStep: midas.rodent.pipeline.onShowStepCallback,  // triggers when showing a step
    onFinish: midas.rodent.pipeline.onFinishCallback  // triggers when Finish button is clicked
  }
  );


  $('#wizard').show();

});






midas.rodent.pipeline.onLeaveStepCallback = function(obj)
  {
  var step_num= obj.attr('rel'); // get the current step number
  return midas.rodent.pipeline.validateSteps(step_num); // return false to stay on step and true to continue navigation
  }

midas.rodent.pipeline.onFinishCallback = function()
  {
   if(midas.rodent.pipeline.validateAllSteps())
     {
     var requestData = {};
     var prefix = json.inputs.prefix;
     var controller_path = json.inputs.controllerPath;
     var cases_class = prefix+'casefolder';
     $.each($("."+cases_class), function(index, input) {
         if(input.checked) {
             requestData[input.id] = input.checked; 
         }
     });
     var multiitem_class = prefix+'multiitem';
     $.each($("."+multiitem_class), function(index, input) {
         if(input.checked) {
             requestData[input.id] = input.checked; 
         }
     });


     $.each($("."+prefix + "cases_suffix"), function(index, input) {
         requestData[input.id] = input.value;
     });

     $.each($("."+prefix + "cases_suffix_connected_hidden"), function(index, input) {
         // remove connected_hidden_ from var id
         var id = input.id.replace('connected_hidden_', '');
         requestData[id] = input.value;
     });
     
     $.each($("."+prefix + "cases_multicheck_suffix"), function(index, input) {
         if(input.type === "checkbox" && input.checked) {
             requestData[input.id] = input.value;
         }
     });
     
     

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
     var cases_folder_id = $('#'+prefix+'casesdirectory').val();
     requestData['casesFolderId'] = cases_folder_id;
     $.ajax(
       {
       type: "POST",
       url: json.global.webroot+"/rodent/"+controller_path+"/startjob",
       data: requestData,
       success: function(data, textStatus)
         {
         var response = jQuery.parseJSON(data);
         console.log(response);
         // now redirect to view the cases folder
         window.location.replace($('.webroot').val()+'/folder/'+response.output_folder_id);
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


midas.rodent.pipeline.selectionCallbacks = {};




midas.rodent.pipeline.validateSteps = function(stepnumber)
  {
  var isStepValid = true;
  $('#wizard').smartWizard('setError',{stepnum:1,iserror:false});
  // validate step 1 if a cases step exists, be sure that
  // at least one case is selected
  if(stepnumber == 1 && json.inputs.cases) {
      var folder_class = json.inputs.prefix + "casefolder";
      var checked = $('.'+folder_class+':checked')
      if(!checked || checked.length == 0) {
          $('#wizard').smartWizard('setError',{stepnum:1,iserror:true});
          $('#wizard').smartWizard('showMessage','Select at least one case and then click Next.');
          return false;    
      }
  }
  // 
  // TODO how to validate here with different pipelines?
  return true;
  }


// TODO how to validate here with different pipelines?
midas.rodent.pipeline.validateAllSteps = function()
  {
  return midas.rodent.pipeline.validateSteps(1) && 
         midas.rodent.pipeline.validateSteps(2) && 
         midas.rodent.pipeline.validateSteps(3) && 
         midas.rodent.pipeline.validateSteps(4);
  }

midas.rodent.pipeline.onShowStepCallback = function(obj)  {
    var step_num = obj.attr('rel'); // get the current step number
    var prefix = json.inputs.prefix;
    var processStepType = json.processSteps[step_num]['type'];
    var processStepId = json.processSteps[step_num]['id'];
    var processStepTitle = json.processSteps[step_num]['title'];
    var processStepDefault = json.processSteps[step_num]['default'];
  
    if(processStepType === "cases") {
        var id = prefix + processStepId;
        var subFolders = json.inputs.casesFolderNames;
        var subFolderVariables = json.inputs.caseFolderVariables;
        midas.rodent.pipeline.selectionCallbacks[id] = midas.rodent.util.createCasesCallback(prefix, step_num, subFolders, subFolderVariables);
        var callback = midas.rodent.pipeline.selectionCallbacks[id];
        if(json.inputs.defaultCasesFolder) {
            callback(json.inputs.defaultCasesFolder);
        }
        

        
        $('#'+id+'_button').click(function(){
            midas.loadDialog("selectfolder_outputfolder","/browse/selectfolder");
            midas.showDialog('Browse for Cases folder');
            midas.rodent.pipeline.currentBrowser = id;
        });
    }
    if(processStepType === "multiItems") {
        var id = prefix + processStepId;
        midas.rodent.pipeline.selectionCallbacks[id] = midas.rodent.util.createMultiItemCallback(prefix, processStepId, step_num);
        $('#'+id+'_button').click(function(){
            midas.loadDialog("selectfolder_outputfolder","/browse/selectfolder");
            midas.showDialog('Browse for ' + processStepTitle + ' folder');
            midas.rodent.pipeline.currentBrowser = id;
        });
        if(processStepDefault) {
            midas.rodent.pipeline.currentBrowser = id;
            var folder_name = processStepDefault['folder_path'];
            var folder_id = processStepDefault['folder_id'];
            var item_ids = processStepDefault['item_ids'];
            console.log(item_ids);
            folderSelectionCallback(folder_name, folder_id);
            $.each(item_ids, function(index, item_id) {
                var item_id_selector = '#' + prefix+"multiitem_"+processStepId+"_"+item_id;
                console.log(item_id_selector);
                $(item_id_selector).attr('checked','checked');
            });
        }
    }
    if(processStepType === "singleItems") {
        $.each(json.inputs.singleItems, function(itemId, item){
            var id = prefix + itemId;
            $('#'+id+'_button').click(function(){
                var label = json.inputs.singleItems[itemId]['label'];
                midas.loadDialog("selectitem_inputitem","/browse/selectitem");
                midas.showDialog('Browse for '+ label);
                midas.rodent.pipeline.currentBrowser = id;
            });
        });
    }
}



itemSelectionCallback = function(name, id)
  {
  $('#'+midas.rodent.pipeline.currentBrowser+'_name').html(name);
  $('#'+midas.rodent.pipeline.currentBrowser).val(id);
  return;
  }

folderSelectionCallback = function(folder_name, folder_id)
  {
  $('#'+midas.rodent.pipeline.currentBrowser+'_name').html(folder_name);
  $('#'+midas.rodent.pipeline.currentBrowser).val(folder_id);
  var callBack = midas.rodent.pipeline.selectionCallbacks[midas.rodent.pipeline.currentBrowser];
  callBack(folder_id);
  return;
  }
