$(document).ready(function () {


    $('.ip-modal-btn').on('click', function () {
        var ip = $(this).data('ip');  // Get IP from data attribute
        // Show the IP in the modal
        $('#modalIpDisplay').text(ip);
    });

    $('.save-ip').click(function() {
        
        let ipField = $("#ipAddress");
        let errorMsg = $("#error-msg");
        let keyid = $('#keyIdMod');

        if (validator.isIP(ipField.val())) {
            ipField.removeClass("is-invalid");
            errorMsg.text("");

            let data = {
                "customaction": "change_ip_address",
                "ip_address": ipField.val(),
                "key_id":keyid.val()
            };

            $.ajax({
                type: "POST",
                url: "",
                data: data,
                dataType: "json",
                beforeSend() {
                    $(".save-ip").prop('disabled', true);
                    $(".save-ip").append('<i class="fa fa-spinner fa-spin ml-2" aria-hidden="true"></i>');
                },
                
                success(result) {
                    console.log(result);

                    if (result.status === true ) {
                        // Show success message
                        $(".modal-body").html('<div class="alert alert-success">IP Address updated successfully!</div>');
                        setTimeout(function () {
                            $("#ipModal").modal("hide"); // Hide modal after 2 seconds
                            location.reload(); // Refresh page
                        }, 2000);
                    } else {
                        // Show error message dynamically from API response
                        let errorMessage = result.data.desc || "Failed to update IP Address. Please try again.";
                        $(".modal-body").prepend(`<div class="alert alert-danger">${errorMessage}</div>`);
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error:", error);
                    console.error("Response Text:", xhr.responseText);

                    // Show generic error inside modal
                    $(".modal-body").prepend('<div class="alert alert-danger">An error occurred. Please try again later.</div>');
                },
                complete() {
                    $(".save-ip").prop("disabled", false);
                    $(".save-ip i.fa-spinner").remove();
                }

                
            });
            
        } else {
            ipField.addClass("is-invalid");
            errorMsg.text("Invalid IP Address. Please enter a valid IP address.");
        }

       
    });

    /** clicnetarea toggle div */
    $(".toggle-btn").click(function () {
        var targetId = $(this).data("target");
        $("#" + targetId).toggleClass("d-none");
        // $(this).text(function (i, text) {
        //     return text === "Show Details" ? "Hide Details" : "Show Details";
        // });
    });
    

    

});


/** copy the keyid */
function copyTokeyId() {
    var keyIdText = $("#keyId").text(); // Get Key ID text
    navigator.clipboard.writeText(keyIdText).then(() => {
        var copyBtn = $(".copy-btn");
        copyBtn.text("Copied!"); // Change button text to "Copied!"
        setTimeout(() => {
            copyBtn.html('<i class="fas fa-copy"></i>'); // Revert to copy icon after 2 seconds
        }, 2000);
    }).catch(err => {
        console.error("Failed to copy: ", err);
    });
}

/** copy the command */
function copyCommand(btn) {
    var commandText = $(btn).parent().text().trim(); // Get command text
    navigator.clipboard.writeText(commandText).then(() => {
        $(btn).html("Copied!"); // Change to check icon
        
        setTimeout(() => {
            $(btn).html('<i class="fas fa-copy"></i>'); // Revert to copy icon
        }, 2000);
    }).catch(err => {
        console.error("Failed to copy: ", err);
    });
}





function validateIP() {
    let ipField = document.getElementById("ipAddress");
    let errorMsg = document.getElementById("error-msg");

    if (validator.isIP(ipField.value)) {
        ipField.classList.remove("is-invalid");
        errorMsg.textContent = "";
        let data = {"customaction": "change_ip_address", "ip_address": ipField.value};
        ajax(JSON.stringify(data), ".save-ip");
        // $('#ipModal').modal('hide'); //Close modal on success
    } else {
        ipField.classList.add("is-invalid");
        errorMsg.textContent = "Invalid IP Address. Please enter a valid IP address.";
    }
}

function ajax(data, classId) {
    console.log(data);
    $.ajax({
        url: "",
        // url: '../modules/addons/openprovider_plesk_license/lib/ajax.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        processData: false,
        contentType: 'application/json',
        success: function (parsedResponse) {
            console.log(parsedResponse);
            $(classId + " i.fa-spinner").remove();
            if (parsedResponse.status === true) {
                jQuery.growl.notice({ title: "Success", message: parsedResponse.message, duration: 3000 });
                setTimeout(function () {
                    location.reload();
                }, 2000);
            } else {
                jQuery.growl.error({ title: "Error", message: parsedResponse.message, duration: 3000 });
            }
            $(classId).prop('disabled', false);
        }
    });
}