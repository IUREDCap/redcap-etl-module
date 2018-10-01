$(function() {
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
});
