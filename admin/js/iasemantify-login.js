var $ = jQuery;

var snackBarOptions = {
    htmlAllowed: true,
    style: 'toast',
    timeout: 3000
};

function iasemantify_saveApiKey(key, secret) {
    $('#iasemantify-websiteUID').val(key);
    $('#iasemantify-websiteSecret').val(secret);
    $('#iasemantify-submit-apikey').click();
}
var websiteUID = null;
var websiteSecret = null;
function iasemantify_addLogin() {
    websiteUID = null;
    websiteSecret = null;
    var  iasemantify_addWebsites = function () {
        InstantAnnotation.util.httpGetHeaders(InstantAnnotation.util.semantifyUrl + "/api/website", { 'Authorization': 'Bearer ' + semantifyToken }, function (websiteRes) {
            if (websiteRes) {
                $('#iasemantify_loginSection').after('<div class="list-group" id="iasemantify_my_websites"><h4>Your websites: (Select one to save your annotation to) </h4> </div>');
                websiteRes.forEach(function (ele) {
                    $('#iasemantify_my_websites').append('<label style="color: #555;"><input type="radio" name="iasemantify_radioName" id="iasemantify_' + ele["uid"] + '" ></input> ' + ele["name"] + ' (' + ele["domain"] + ')' + ' </label><br>');
                    $('#iasemantify_' + ele["uid"]).click(function () {
                        websiteUID = ele["uid"];
                        websiteSecret = ele["secret"];
                    });
                    snackBarOptions["content"] = "Successfully logged in. Please select a Website.";
                    $.snackbar(snackBarOptions);
                });
                $('#iasemantify_my_websites').append('<button id="iasemantify_selectRadioId" class="btn btn-sm button-sti-red">Select</button>');
                $('#iasemantify_selectRadioId').click(function () {
                    if (websiteUID != null && websiteSecret != null) {
                        $('#iasemantify_selectRadioId').slideUp(100);
                        iasemantify_saveApiKey(websiteUID, websiteSecret);
                    } else {
                        alert("Choose a website!")
                    }
                });
            } else {
                snackBarOptions["content"] = "There has been an error when retrieving your websites";
                $.snackbar(snackBarOptions);
            }
        });
    };

    $('#iasemantify_loginSection').append(
        '<h4>Don\'t know your api-keys? You can login to semantify here to find your website!</h4>' +
        '<button type="button" class="btn btn-sm button-sti-red" id="iasemantify_loginBtn" style="margin:10px 0px 10px 0px; ">Login</button>' +
        //style on login btn is because the icon makes the button larger
       
        '<div id="iasemantify_credentialsSection" hidden>' +
        '<input type="text" class="form-control" style="background:white;" id="iasemantify_username" placeholder="Username/Email" title="Username/Email">' +
        '<input type="password" class="form-control"  style="background:white" id="iasemantify_password" placeholder="Password" title="Password">' +
        '</div>'+
        '<br><a  target="_blank" href="https://semantify.it/register">Register</a>'
    );

    var loginOnEnter = function (event) {
        if (event.keyCode === 13) {
            $("#iasemantify_loginBtn").click();
        }
    };

    $("#iasemantify_username").keyup(function (event) {
        loginOnEnter(event);
    });
    $("#iasemantify_password").keyup(function (event) {
        loginOnEnter(event);
    });

    $('#iasemantify_loginBtn').click(function () {
        if ($('#iasemantify_credentialsSection').css('display') === 'none') {
            $('#iasemantify_credentialsSection').slideDown(100);
        }
        else {
            var credentials = {
                identifier: $('#iasemantify_username').val(),
                password: $('#iasemantify_password').val()
            };

            InstantAnnotation.util.httpPostJson(InstantAnnotation.util.semantifyUrl + "/api/login", null, credentials, function (loginResp) {
                if (loginResp && loginResp.statusText!="Unauthorized") {
                    $('#iasemantify_loginSection').slideUp(100);
                    semantifyToken = loginResp["token"];
                    iasemantify_addWebsites();
                }
                else {
                    snackBarOptions["content"] = "Couldn't log in to semantify.it";
                    $.snackbar(snackBarOptions);
                }
            });
        }
    });
}
