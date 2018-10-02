$(function() {
    // Copy server form
    copyForm = $("#copy-form").dialog({
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
    $(".copyServer").click(function(){
        var id = this.id;
        var server = id.substring(5);
        $("#server-to-copy").text('"'+server+'"');
        $('#copy-from-server-name').val(server);
        $("#copy-form").data('server', server).dialog("open");
    });
    
    // Rename server form
    renameForm = $("#rename-form").dialog({
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
    
    $(".renameServer").click(function(){
        var id = this.id;
        var server = id.substring(7);
        $("#server-to-rename").text('"'+server+'"');
        $('#rename-server-name').val(server);
        $("#rename-form").data('server', server).dialog("open");
    });
    
        
    // Delete server form
    deleteForm = $("#delete-form").dialog({
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
    
    $(".deleteServer").click(function(){
        var id = this.id;
        var server = id.substring(7);
        $("#server-to-delete").text('"'+server+'"');
        $('#delete-server-name').val(server);
        $("#delete-form").data('server', server).dialog("open");
    });
});
