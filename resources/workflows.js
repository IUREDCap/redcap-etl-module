//-------------------------------------------------------
// Copyright (C) 2019 The Trustees of Indiana University
// SPDX-License-Identifier: BSD-3-Clause
//-------------------------------------------------------

if (typeof RedCapEtlModule === 'undefined') {
    var RedCapEtlModule = {};
}

RedCapEtlModule.deleteWorkflow = function (event) {
    var workflow = event.data.workflow;
    $("#workflow-to-delete").text('"'+workflow+'"');
    $('#delete-workflow-name').val(workflow);
    $("#delete-form").data('workflow', workflow).dialog("open");
}

RedCapEtlModule.reinstateWorkflow = function (event) {
    var workflow = event.data.workflow;
    $("#workflow-to-reinstate").text('"'+workflow+'"');
    $('#reinstate-workflow-name').val(workflow);
    $("#reinstate-form").data('workflow', workflow).dialog("open");
}

$(function() {
    "use strict";

    // Delete workflow dialog
    var deleteForm = $("#delete-form").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete workflow": function() {deleteForm.submit();}
        },
        title: "Delete workflow"
    });

   // Reinstate workflow dialog
    var reinstateForm = $("#reinstate-form").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Reinstate workflow": function() {reinstateForm.submit();}
        },
        title: "Reinstate workflow"
    })

});

