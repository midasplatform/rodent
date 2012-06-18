var midas = midas || {};
midas.rodent = midas.rodent || {};
midas.rodent.util = midas.rodent.util || {};

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
                  
                $.each(subFolders, function(ind, subFolder) {
                    var variables = subFoldersVariables[subFolder];
                    $.each(variables, function(var_ind, variable) {
                        var suffixSelectId = prefix + "cases_suffix_"+variable.varname;
                        var suffixSelectClass = prefix + "cases_suffix";
                        var suffixSelectRow = '<tr><td>'+variable.label + '</td><td><select class="'+suffixSelectClass+'" id="'+suffixSelectId+'"></td></tr>';
                        $('#'+suffixes_ul_id).append(suffixSelectRow);
                        
                        console.log(variable);
                        ajaxWebApi.ajax({
                            method: 'midas.rodent.list.case.suffixes',
                            args: 'folder_id=' + folder_id + "&selected_subfolder_name="+subFolder,
                            success: function(results) {
                                $.each(results.data.suffixes, function(index, suffix) {
                                    var suffixOption = '<option value='+suffix+'>'+suffix+'</option>'; 
                                    $('#'+suffixSelectId).append(suffixOption);
                                });
                            } //success
                        }); //ajax
                    }); //each variables
                });  // seach subFolders
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