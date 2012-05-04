var midas = midas || {};
midas.rodent = midas.rodent || {};
midas.rodent.ss = midas.rodent.ss|| {};

midas.rodent.ss.currentBrowser = false;
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
    onLeaveStep: midas.rodent.ss.onLeaveStepCallback, // triggers when leaving a step
    onShowStep: midas.rodent.ss.onShowStepCallback,  // triggers when showing a step
    onFinish: midas.rodent.ss.onFinishCallback  // triggers when Finish button is clicked
  }
  );


  $('#wizard').show();

});






midas.rodent.ss.onLeaveStepCallback = function(obj)
  {
  var step_num= obj.attr('rel'); // get the current step number
  return midas.rodent.ss.validateSteps(step_num); // return false to stay on step and true to continue navigation
  }

midas.rodent.ss.onFinishCallback = function()
  {
   if(midas.rodent.ss.validateAllSteps())
     {
     requestData = {};
     
     var prefix = json.inputs.prefix;
     var cases_class = prefix+'casefolder';
     $.each($("."+cases_class), function(index, input) {
         if(input.checked) {
             requestData[input.id] = input.checked; 
         }
     });
     var suffix = prefix+'suffix';
     requestData[suffix] = $('#'+suffix).val();
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
       url: json.global.webroot+"/rodent/ss/startjob",
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


midas.rodent.ss.selectionCallbacks = {};




midas.rodent.ss.validateSteps = function(stepnumber)
  {
  var isStepValid = true;
  // validate step 1
  //HACK for now, no validation
  return true;
  }

midas.rodent.ss.validateAllSteps = function()
  {
  return midas.rodent.ss.validateSteps(1) && 
         midas.rodent.ss.validateSteps(2) && 
         midas.rodent.ss.validateSteps(3) && 
         midas.rodent.ss.validateSteps(4);
  }

midas.rodent.ss.onShowStepCallback = function(obj)
  {
  var step_num = obj.attr('rel'); // get the current step number
  if(step_num == 1)
    {
    var prefix = json.inputs.prefix;
    var id = prefix + "casesdirectory";
    
    // create a callback to run after selecting the cases folder
    var casesCallback = function(folder_id) {
        // get the list of cases from the server
        // setup checkboxes to allow the user to select a subset of cases
        ajaxWebApi.ajax({
            method: 'midas.folder.children',
            args: 'id=' + folder_id,
            success: function(results) {
                // find all the folder children of the selected folder
                // add a checkbox for each of them so the user can select cases
                // TODO remove the checkboxes_div or else disable browse folders button 
                // because if you keep selecting a folder the checkboxes keep getting added
                var checkbox_div = $('#step-1').append('<div id="case_folders_checkboxes_div"></div>');
                checkbox_div.append("Select the cases to run:");
                var rows = "<ul>";
                $.each(results.data.folders, function(ind, folder) {
                    var row_li = '<li><span><input type="checkbox" class="'+prefix+'casefolder" id="'+prefix+"casefolder_"+folder.folder_id+'" />'+folder.name+'</span></li>';                    
                    rows = rows + row_li;
                });
                rows = rows + "</ul>";
                checkbox_div.append(rows);

                var suffix_span = '<span><input type="text" id="'+prefix+'suffix" />Suffix</span>';                    
                checkbox_div.append(suffix_span);

            }
        });
    };
    midas.rodent.ss.selectionCallbacks[id] = casesCallback;

    $('#'+id+'_button').click(function(){
        midas.loadDialog("selectfolder_outputfolder","/browse/selectfolder");
        midas.showDialog('Browse for Cases folder');
        currentBrowser = id;
    });
    
    }
  if(step_num == 2)
    {
    var prefix = json.inputs.prefix;
    var items = json.inputs.items;
    $.each( items , function(k, v){
      var id = prefix + k;
      $('#'+id+'_button').click(function(){
          midas.loadDialog("selectitem_inputitem","/browse/selectitem");
          midas.showDialog('Browse');
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

folderSelectionCallback = function(folder_name, folder_id)
  {
  $('#'+currentBrowser+'_name').html(folder_name);
  $('#'+currentBrowser).val(folder_id);
  var callBack = midas.rodent.ss.selectionCallbacks[currentBrowser];
  callBack(folder_id);
  return;
  }
