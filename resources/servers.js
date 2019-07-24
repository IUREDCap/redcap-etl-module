//-------------------------------------------------------
// Copyright (C) 2019 The Trustees of Indiana University
// SPDX-License-Identifier: BSD-3-Clause
//-------------------------------------------------------

if (typeof RedCapEtlModule === 'undefined') {
    var RedCapEtlModule = {};
}

RedCapEtlModule.copyServer = function (event) {
    var server = event.data.server;
    $("#server-to-copy").text('"'+server+'"');
    $('#copy-from-server-name').val(server);
    $("#copy-form").data('server', server).dialog("open");
}
    
RedCapEtlModule.renameServer = function (event) {
    var server = event.data.server;
    $("#server-to-rename").text('"'+server+'"');
    $('#rename-server-name').val(server);
    $("#rename-form").data('server', server).dialog("open");
}

RedCapEtlModule.deleteServer = function (event) {
    var server = event.data.server;
    $("#server-to-delete").text('"'+server+'"');
    $('#delete-server-name').val(server);
    $("#delete-form").data('server', server).dialog("open");
}

$(function() {
    "use strict";

    // Copy server dialog
    var copyForm = $("#copy-form").dialog({
        autoOpen: false,
        height: 200,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Copy server": function() {copyForm.submit(); $(this).dialog("close");}
        },
        title: "Copy server"
    });
    
    // Rename server dialog
    var renameForm = $("#rename-form").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Rename server": function() {renameForm.submit();}
        },
        title: "Rename server"
    });
    
        
    // Delete server dialog
    var deleteForm = $("#delete-form").dialog({
        autoOpen: false,
        height: 220,
        width: 400,
        modal: true,
        buttons: {
            Cancel: function() {$(this).dialog("close");},
            "Delete server": function() {deleteForm.submit();}
        },
        title: "Delete server"
    });

});

