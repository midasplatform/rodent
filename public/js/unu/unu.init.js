var midas = midas || {};
midas.rodent = midas.rodent || {};
midas.rodent.unu = midas.rodent.unu || {};

var currentBrowser = false;
var inittializedExecutableForm = false;
var executableValid = false;
var isExecutableMeta = false;
var isDefineAjax = true;
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
    onLeaveStep: onLeaveStepCallback, // triggers when leaving a step
    onShowStep: onShowStepCallback,  // triggers when showing a step
    onFinish: onFinishCallback  // triggers when Finish button is clicked
  }
  );

  $('#uploadContentBlock').load(json.global.webroot+'/upload/simpleupload');
  $('#wizard').show();

  if($('#selectedExecutableId').val() != '')
    {
    executableValid = true;
    isExecutableMeta = true;
    }
});
















// maybe a different structure with each step having a
// onLeaveStepCallback (a validateStep)
// onShowStepCallback
// should have their own hooks into: function itemSelectionCallback(name, id)
// function folderSelectionCallback(name, id) ??
// not sure if this is a good idea, but would keep info about a step more
// logically together








function onLeaveStepCallback(obj)
  {
  var step_num= obj.attr('rel'); // get the current step number
  return validateSteps(step_num); // return false to stay on step and true to continue navigation
  }

function onFinishCallback()
  {
   if(validateAllSteps())
     {
     req = {'inputItemId' : $('#midas_rodent_unu_selectedImageId').val(),
            'outputFolderId' : $('#midas_rodent_unu_selectedFolderId').val(),
            'aValue' : $('#midas_rodent_unu_aValue').val(),
            'pValue' : $('#midas_rodent_unu_pValue').val()};
     $(this).after('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Saving..." />')
     $(this).remove();
     $.ajax(
       {
       type: "POST",
       url: json.global.webroot+"/rodent/unu/startjob",
       data: req
/*       success: function(x)
         {
            
         }
         window.location.replace($('.webroot').val()+'/remoteprocessing/job/manage')
         }
       */
       });
     }
   else
     {
     createNotive("There are some errors.", 4000);
     }
  }


function validateSteps(stepnumber)
  {
  var isStepValid = true;
  // validate step 1
//HACK for now
return true;

  if(stepnumber == 1)
    {
    // clean up this validation, still based on executable
    
    if($('#selectedExecutableId').val() == '' || executableValid == false || isExecutableMeta == false)
      {
      createNotive("Please select an Executable and set its Option information", 4000);
      isStepValid = false;
      }
    }


if(stepnumber == 2)
    {
    if($('#selectedExecutableId').val() == '' || executableValid == false || isExecutableMeta == false)
      {
      createNotive("Please select an Executable and set its Option information", 4000);
      isStepValid = false;
      }
    }

  if(stepnumber == 3)
    {
    var i = 0;
    results = new Array();
    if($('#jobName').val() == '')
      {
      createNotive('Please set the job\'s name.', 4000);
      isStepValid = false;
      }
    $('.optionWrapper').each(function(){
    var required = false;
    if($(this).attr('isrequired') == 'true')
      {
      required = true;
      }

    if($(this).find('.selectedFolder').length > 0)
      {
      if($(this).find('.nameOutputOption').val() == '' || $(this).find('.selectedFolder').attr('element') == '')
        {
        if(required) createNotive('Please set '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else if($(this).find('.nameOutputOption').val().indexOf(".") == -1)
        {
        if(required) createNotive('Please set an extension in the option '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else
        {
        results[i] = $(this).find('.selectedFolder').attr('element')+';;'+$(this).find('.nameOutputOption').val();
        }
      }
    else if($(this).find('.selectInputFileLink').length > 0)
      {
      if($(this).find('.selectedItem').attr('element') == '' && $(this).find('.selectedFolderContent').attr('element') == '')
        {
        if(required) createNotive('Please set '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else
        {
        var folderElement = $(this).find('.selectedFolderContent').attr('element');
        if(folderElement != '')
          {
          results[i] = 'folder'+folderElement;
          }
        else
          {
          results[i] = $(this).find('.selectedItem').attr('element');
          }
        }
      }
    else
      {
      if($(this).find('.valueInputOption').val() == '')
        {
        if(required) createNotive('Please set '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else
        {
        results[i] = $(this).find('.valueInputOption').val();
        }
      }

    i++;
    });
    }

  if(isStepValid)
    {
    $('#wizard').smartWizard('setError',{stepnum:stepnumber,iserror:false});
    }
  else
    {
    $('#wizard').smartWizard('setError',{stepnum:stepnumber,iserror:true});
    }
  return isStepValid;
  }

function validateAllSteps()
  {
  return validateSteps(1) && validateSteps(2) && validateSteps(3) && validateSteps(4);
  }

function onShowStepCallback(obj)
  {
  var step_num = obj.attr('rel'); // get the current step number
  if(step_num == 1)
    {
    $('#midas_rodent_unu_browseImageFile').click(function(){
      loadDialog("selectitem_imageinput","/browse/selectitem");
      showDialog('Browse');
      currentBrowser = 'imageinput';
    });
    }
  if(step_num == 2)
    {
    $('#midas_rodent_unu_browseOutputFolder').click(function(){
      loadDialog("selectfolder_outputfolder","/browse/selectfolder");
      showDialog('Browse');
      currentBrowser = 'folderoutput';
    });
    }

  }


/*
function initExecutableForm()
{
  inittializedExecutableForm = true;
  $( "#datepicker" ).datetimepicker();
  $('#ui-datepicker-div').hide();
  $('#checkboxSchedule').change(function(){
    if(!$(this).is(':checked'))
      {
      $('#schedulerWrapper').show();
      }
    else
      {
       $('#schedulerWrapper').hide();
      }
  })

  $('.selectInputFileLink').click(function(){
    loadDialog("selectitem_"+$(this).attr('order'),"/browse/selectitem");
    showDialog('Browse');
    currentBrowser = $(this).attr('order');
  });

  $('.selectOutputFolderLink').click(function(){
    loadDialog("selectfolder_"+$(this).attr('order'),"/browse/selectfolder?policy=write");
    showDialog('Browse');
    currentBrowser = $(this).attr('order');
  });

  $('.selectInputFolderLink').click(function(){
    loadDialog("selectfolder_"+$(this).attr('order'),"/browse/selectfolder?policy=read");
    showDialog('Browse');
    currentBrowser = $(this).attr('order');
  });
  $('[qtip]').qtip({
     content: {
        attr: 'qtip'
     }
  })
}*/

function itemSelectionCallback(name, id)
  {
  if(currentBrowser == 'imageinput')
    {
    $('#selectedImage').html(name);
    $('#selectedImageId').val(id);
    $('#midas_rodent_unu_selectedImage').html(name);
    $('#midas_rodent_unu_selectedImageId').val(id);
    return;
    }
  }

function folderSelectionCallback(name, id)
  {
  if(currentBrowser == 'folderoutput')
    {
    $('#midas_rodent_unu_selectedFolder').html(name);
    $('#midas_rodent_unu_selectedFolderId').val(id);
    return;
    }
  }
  