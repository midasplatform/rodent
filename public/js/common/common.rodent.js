var midas = midas || {};
midas.rodent = midas.rodent || {};
midas.rodent.util = midas.rodent.util || {};


midas.rodent.util.populateSubfolderSelect = function(folder_id, subfolder_name, select_id, optional) {
    ajaxWebApi.ajax({
        method: 'midas.rodent.list.case.suffixes',
        args: 'folder_id=' + folder_id + "&selected_subfolder_name="+subfolder_name,
        success: function(results) {
            if(optional) {
                // add in a blank suffix to exclude this variable
                var suffixOption = '<option value="">None</option>';     
                $('#'+select_id).append(suffixOption);
            }
            $.each(results.data.suffixes, function(index, suffix) {
                var suffixOption = '<option value='+suffix.value+'>'+suffix.label+'</option>'; 
                $('#'+select_id).append(suffixOption);
            });
        } //success
    }); //ajax
};



// create a callback to run after selecting the cases folder
midas.rodent.util.createCasesCallback = function(prefix, stepNumber, subFolders, subFoldersVariables) {
    return function(folder_id) {
        // get the list of cases from the server
        // setup checkboxes to allow the user to select a subset of cases
        ajaxWebApi.ajax({
            method: 'midas.folder.children',
            args: 'id=' + folder_id,
            success: function(results) {
                // find all the folder children of the selected folder
                // add a checkbox for each of them so the user can select cases
                $('#case_folders_checkboxes_div').remove();
                $('#step-'+stepNumber).append('<div id="case_folders_checkboxes_div" class="pipeline_checkboxes_div"></div>');
                var checkbox_div = $('#case_folders_checkboxes_div');
                checkbox_div.append("Select the cases to run:");
                var rows = "<ul>";
                $.each(results.data.folders, function(ind, folder) {
                    var row_li = '<li><span><input type="checkbox" class="'+prefix+'casefolder" id="'+prefix+"casefolder_"+folder.folder_id+'" />'+folder.name+'</span></li>';                    
                    rows = rows + row_li;
                });
                rows = rows + "</ul>";
                checkbox_div.append(rows);

                var suffixes_ul_id = prefix + 'suffixes_ul';
                var suffixes_ul = '<table id="'+suffixes_ul_id+'"></table>';    
                checkbox_div.append(suffixes_ul);
                  
                // for each of the subfolders to get inputs from  
                $.each(subFolders, function(ind, subFolder) {
                    var variables = subFoldersVariables[subFolder];
                    // create a dropdown for each variable
                    $.each(variables, function(var_ind, variable) {
                        var suffixSelectId = prefix + "cases_suffix_"+variable.varname;
                        var suffixSelectClass = prefix + "cases_suffix";
                        var suffixSelectRow = '<tr><td>'+variable.label + '</td><td><select class="'+suffixSelectClass+'" id="'+suffixSelectId+'"></td></tr>';
                        $('#'+suffixes_ul_id).append(suffixSelectRow);
                        
                        // populate the dropdown with suffixes from the subfolder entries
                        midas.rodent.util.populateSubfolderSelect(folder_id, subFolder, suffixSelectId, variable.optional);
                    }); //each variables
                });  // each subFolders
                
                // now for each of the dropdowns tied to folders
                $.each(json.inputs.caseFolderDropdownVariables, function(varname, dropdownparams) {

                    // create a dropdown that can select between the dropdownvalues
                    var initialSelectClass = prefix + "cases_initial";
                    var initialSelectId = prefix + "cases_initial_"+varname;
                    var connectedLabelCellId = initialSelectId + "_label_cell";
                    var connectedDropdownCellId = initialSelectId + "_dropdown_cell";
                    var initialSelectRow = '<tr><td>'+dropdownparams['label'] + '</td><td><select class="'+initialSelectClass+'" id="'+initialSelectId+'"></td><td id="'+connectedLabelCellId+'"></td><td id="'+connectedDropdownCellId+'"></td></tr>';
                    $('#'+suffixes_ul_id).append(initialSelectRow);
                    
                    
                    function initialSelectChanged(selectedVal) {
                        // clear out the existing cells associated with this id
                        $('#'+connectedLabelCellId).empty();
                        $('#'+connectedDropdownCellId).empty();
                        
                        // get the connected folder params associated with this value
                        var connectedParams = dropdownparams['connected'][selectedVal];
                        // create the connected dropdown
                        var suffixSelectId
                        var connectedSuffixSelectId = prefix + "cases_suffix_"+connectedParams.varname;
                        var suffixSelectClass = prefix + "cases_suffix";
                        var connectedSuffixDropdown = '<select class="'+suffixSelectClass+'" id="'+connectedSuffixSelectId+'">';
                        $('#'+connectedLabelCellId).html(connectedParams.label);                        
                        $('#'+connectedDropdownCellId).html(connectedSuffixDropdown);                        

                        // populate the dropdown with suffixes from the subfolder entries
                        midas.rodent.util.populateSubfolderSelect(folder_id, connectedParams.subFolder, connectedSuffixSelectId, connectedParams.optional);

                        // remove all hidden variables of this type
                        // this won't work if we have more than one grouping, but we only have one for now
                        var hiddenClass = prefix + "cases_suffix_connected_hidden";
                        $('.'+hiddenClass).remove();

                        // now add in hidden variables for others than the connected suffix dropdown
                        // and only add them uniquely as some rows may share a variable
                        $.each(dropdownparams['connected'], function(dropdownvalue, inputfoldervariable) {
                            if(selectedVal !== dropdownvalue) {
                                // check existence first so we don't double add hiddens of the same id
                                // also check that a select doesn't exist tied to this varname
                                var hiddenId = prefix + "cases_suffix_connected_hidden_" + inputfoldervariable.varname;
                                var existingHiddens = $('#'+hiddenId);
                                var connectedSuffixSelectId = prefix + "cases_suffix_" + inputfoldervariable.varname;
                                var existingConnecteds = $('#'+connectedSuffixSelectId);
                                if(existingHiddens.length === 0 && existingConnecteds.length === 0) {
                                    checkbox_div.append('<input type="hidden" class="'+hiddenClass+'" id="'+hiddenId+'" value=""/>');
                                }
                            }
                        });

                         
                    }
                    
                    // create an option in the initial drop down for each of the values
                    // also create a blank hidden variable for each of these, that should be removed when
                    // that option is chosen
                    $.each(dropdownparams['connected'], function(dropdownvalue, inputfoldervariable) {
                        var initialselectOption = '<option value="'+dropdownvalue+'">'+dropdownvalue+'</option>'; 
                        $('#'+initialSelectId).append(initialselectOption);
                        var hiddenId = prefix + "cases_suffix_connected_hidden_" + inputfoldervariable.varname;
                        var hiddenClass = prefix + "cases_suffix_connected_hidden";
                        // check existence first so we don't double add hiddens of the same id
                        var existingIds = $('#'+hiddenId);
                        if(existingIds.length === 0) {
                            checkbox_div.append('<input type="hidden" class="'+hiddenClass+'" id="'+hiddenId+'" value=""/>');
                        }
                    });
                    
                    // create an event handler for the initial drop down
                    $('#'+initialSelectId).change(function() {
                        var selectedVal = $('#'+initialSelectId).val();
                        initialSelectChanged(selectedVal);
                    });

                    // call the code to set the connected select relative to the initial select's initial value
                    var selectedVal = $('#'+initialSelectId).val();
                    initialSelectChanged(selectedVal);
                });


                // now for each of the multiselects tied to folders
                $.each(json.inputs.caseFolderMultiVariables, function(folder, variables) {
                    console.log(folder);
                    console.log(variables);
                    $.each(variables, function(index, variable) {
                        // create a label on one row
                        var multicheckLabelRowId = prefix + "cases_multicheck_labelrow_" + variable.varname;
                        var labelRow = '<tr id="'+multicheckLabelRowId+'"><td>'+variable.label+'</td></tr>';
                        $('#'+suffixes_ul_id).append(labelRow);
                        var multicheckSuffixClass = prefix + "cases_multicheck_suffix";
                        
                        // now for each suffix create a checkbox
                        ajaxWebApi.ajax({
                            method: 'midas.rodent.list.case.suffixes',
                            args: 'folder_id=' + folder_id + "&selected_subfolder_name="+folder,
                            success: function(results) {
                                $.each(results.data.suffixes, function(index, suffix) {
                                    var checkId = prefix + "cases_multicheck_" + variable.varname + "_" + suffix.label;
                                    var suffixCheckRow = '<tr><td></td><td><input type="checkbox" id="'+checkId+'" class="'+multicheckSuffixClass+'"/></td><td>'+suffix.label+'</td></tr>'; 
                                    $(suffixCheckRow).insertAfter('#'+multicheckLabelRowId);
                                });
                            } //success
                       }); //ajax
                    });
                });
            } // success
        });  // ajax
    }; // return function
};

// create a callback to run after selecting the items folder
midas.rodent.util.createMultiItemCallback = function(prefix, processStepId, stepNumber) {
    return function(folder_id) {
        // get the list of items from the server
        // setup checkboxes to allow the user to select a subset of items
        ajaxWebApi.ajax({
            method: 'midas.folder.children',
            args: 'id=' + folder_id,
            success: function(results) {
                // find all the item children of the selected folder
                // add a checkbox for each of them so the user can select items
                // TODO remove the checkboxes_div or else disable browse folders button 
                // because if you keep selecting a folder the checkboxes keep getting added
                var divId = 'case_multiitems_checkboxes_div_'+stepNumber;
                $('#'+divId).remove();
                $('#step-'+stepNumber).append('<div id="'+divId+'" class="pipeline_checkboxes_div"></div>');
                var checkbox_div = $('#'+divId);
                checkbox_div.append("Select the items:");
                var rows = "<ul>";
                $.each(results.data.items, function(ind, item) {
                    var row_li = '<li><span><input type="checkbox" class="'+prefix+'multiitem" id="'+prefix+"multiitem_"+processStepId+"_"+item.item_id+'" />'+item.name+'</span></li>';                    
                    rows = rows + row_li;
                });
                rows = rows + "</ul>";
                checkbox_div.append(rows);
            }
        });
    };
};