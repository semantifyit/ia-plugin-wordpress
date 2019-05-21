"use strict";
var is_wp = true;
var colClass = "";
if (is_wp) {
    colClass = "col-lg-4 col-md-6 col-sm-6 col-xs-12";
    var $ = jQuery;
} else {
    colClass = "col-lg-3 col-md-4 col-sm-6 col-xs-12";
}
var IA_delete_id_whitelist=[]; //used to whitelist deleted ids;
var iasemantify_sdoProperties;
var iasemantify_sdoPropertiesReady = false;
var iasemantify_sdoClasses;
var iasemantify_sdoClassesReady = false;
var iasemantify_panelId = "IAsemantifyPanel0";
var iasemantify_panelCount = 0;

var panelRoots = [];
var typeList = [];
var inputFields = [];

var semantifyUrl = "https://semantify.it";
//semantifyUrl = "http://localhost:8081";

var semantifyShortUrl = "https://smtfy.it/";
//semantifyShortUrl = "https://staging.semantify.it/api/annotation/short/";

var defaultSemantifyWebsiteUID = "Hkqtxgmkz";
var iasi_saveWebsiteUID = "DEFAULT";
var defaultSemantifyWebsiteSecret = "ef0a64008d0490fc4764c2431ca4797b";
var iasi_saveWebsiteSecret = "DEFAULT";

var semantifyToken;

var wordPressSaveBtn = {
    "name": "Save",
    "icon": "save",
    "createJsonLD": true,
    "onclick": function (res) {
        var bulk = [];
        var toSend = {};
        toSend["content"] = res.jsonLd;
        toSend["dsHash"] = res.dsHash;
        bulk.push(toSend);
        if (res.jsonLd == null) {
            return;
        }
        var iasemantify_saveWebsiteUID = iasi_saveWebsiteUID;
        var iasemantify_saveWebsiteSecret = iasi_saveWebsiteSecret;
        if (iasemantify_saveWebsiteUID === "" || iasemantify_saveWebsiteUID === undefined || iasemantify_saveWebsiteUID === null || iasemantify_saveWebsiteUID === "DEFAULT") {
            iasemantify_saveWebsiteUID = defaultSemantifyWebsiteUID;
        }
        if (iasemantify_saveWebsiteSecret === "" || iasemantify_saveWebsiteSecret === undefined || iasemantify_saveWebsiteSecret === null || iasemantify_saveWebsiteSecret === "DEFAULT") {
            iasemantify_saveWebsiteSecret = defaultSemantifyWebsiteSecret;
        }
        var annUID = $('#panel-' + res.panelId).data("smtfyAnnId");
        var annWebId = $('#panel-' + res.panelId).data("smtfyWebId");
        var annWebSecret = $('#panel-' + res.panelId).data("smtfyWebSecret");
        if (annUID) {
            //update
            var header = {
                'website-secret': annWebSecret
            };

            httpPatchJson(semantifyUrl + "/api/annotation/uid"+ "/" + annUID, header, { "content": res.jsonLd }, function (patchRes) {
                if (patchRes && patchRes.statusText!=="Not Found" && patchRes.statusText!=="Forbidden") {
                    send_snackbarMSG("Successfully updated Annotation");
                }
                else {
                    send_snackbarMSG_fail("An error occurred. Please check your semantify api-key!")
                }
            });
        } else {
            //save
            var header = {
                'website-secret': iasemantify_saveWebsiteSecret
            };
            httpPostJson(semantifyUrl + "/api/annotation/" + iasemantify_saveWebsiteUID, header, bulk, function (saveRes) {
                if (saveRes && saveRes.statusText!=="Not Found" && saveRes.statusText!=="Forbidden") {
                    send_snackbarMSG("Successfully saved Annotation to semantify.it");
                    //$("#panel-footer-btn-Save-" + res.panelId).prop('disabled', true);

                    var ann_id = saveRes[0]["UID"];
                    $('#panel-' + res.panelId)
                        .data("smtfyAnnId", ann_id)
                        .data("smtfyWebId", iasemantify_saveWebsiteUID)
                        .data("smtfyWebSecret", iasemantify_saveWebsiteSecret);

                    var post_id = $('#ia-data').attr("data-post_id");
                    var nonce = $('#ia-data').attr("data-nonce");

                    $.ajax({
                        type: "post",
                        url: myAjax.ajaxurl,
                        data: {
                            action: "iasemantify_push_ann",
                            post_id: post_id,
                            nonce: nonce,
                            ann_id: ann_id,
                            ds_hash: res.dsHash,
                            web_id: iasemantify_saveWebsiteUID,
                            web_secret: iasemantify_saveWebsiteSecret,
                        },
                        success: function (res) {
                        },
                        error: function (err) {
                            send_snackbarMSG_fail("An error occurred while saving to wordpress")
                        }
                    });
                } else {
                    send_snackbarMSG_fail("An error occurred. Please check your semantify api-key!")
                }
            });
        }
    }
};

var wordPressDeleteBtn = {
    "name": "Delete",
    "icon": "close",
    "createJsonLD": false,
    "onclick": function (res) {
        var filled = $('#panel-' + res.panelId).data("smtfyAnnId");
        var annWebId = $('#panel-' + res.panelId).data("smtfyWebId");
        var annWebSecret = $('#panel-' + res.panelId).data("smtfyWebSecret");
        var result;
        remove(IA_currently_added_annotations,filled);
        IA_delete_id_whitelist.push(filled);
        //console.log($('#ia_label_checkbox_'+filled).length);
        //if($('#ia_label_checkbox_'+filled).length!==0){
        //    $('#ia_label_checkbox_'+filled).css('color','');
        //    $('#ia_add_ann_'+filled).prop('disabled',false);
        //}
        if (!filled) {
            result = true;
        } else {
            result = confirm("Do you really want to delete this annotation?");
        }

        if (result) {
            $("#panel-" + res.panelId).hide(500);

            var post_id = $('#ia-data').attr("data-post_id");
            var nonce = $('#ia-data').attr("data-nonce");

            $.ajax({
                type: "post",
                url: myAjax.ajaxurl,
                data: {
                    action: "iasemantify_delete_ann",
                    post_id: post_id,
                    nonce: nonce,
                    ann_id: res.annId,
                    ds_hash: res.dsHash || 'NO_DS',
                    web_id: annWebId,
                    web_secret: annWebSecret,
                },
                success: function (res) {
                },
                error: function (err) {
                    send_snackbarMSG_fail("An error occurred while deleting from wordpress")
                }
            });
        }
    }
};

var copyBtn = {
    "name": "Copy",
    "icon": "content_copy",
    "createJsonLD": true,
    "onclick": function (resp) {
        if (resp.jsonLd)
            copyStr(JSON.stringify(resp.jsonLd, null, 2));
    }
};

var deleteBtn = {
    "name": "Close",
    "icon": "close",
    "createJsonLD": false,
    "onclick": function (resp) {
        $("#panel-" + resp.panelId.toString()).hide();
    }
};

var clearBtn = {
    "name": "Clear",
    "icon": "delete",
    "onclick": function (resp) {
        inputFields.forEach(function (i) {
            var id = i.slice(i.indexOf("_") + 1, i.indexOf("_", i.indexOf("_") + 1));
            if (resp.panelId.toString() === id) {
                $("#" + i).val("");
            }
        })
    }
};

var saveBtn = {
    "name": "Save",
    "icon": "save", //backup
    "onlyIcon": false,
    "createJsonLD": true,
    "onclick": function (resp) {
        if (!resp.jsonLd)
            return;

        var bulk = [];
        var toSend = {};
        toSend["content"] = resp.jsonLd;
        toSend["dsHash"] = resp.dsHash;
        bulk.push(toSend);

        var snackBarOptions = {
            htmlAllowed: true,
            style: 'toast',
            timeout: 3000
        };

        httpPostJson(semantifyUrl + "/api/annotation/" + iasemantify_saveUID, bulk, function (saveRes) {
            if (saveRes) {
                snackBarOptions["content"] = "Successfully saved Annotation to semantify.it";
                $.snackbar(snackBarOptions);

                var annUrl = semantifyShortUrl + saveRes[0]["UID"];
                var dummy = document.createElement("div");
                document.body.appendChild(dummy);
                dummy.setAttribute("id", "IA_preview_id");
                $('#IA_preview_id').append(
                    '<div class="bootstrap semantify">' +
                    '<div class="modal fade" id="IA_saveModal" role="dialog">' +
                    '<div class="modal-dialog">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                    '<h3 class="modal-title">Successfully saved JSON-LD annotation!</h3>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<pre id="IA_preview_textArea" style="max-height: 300px;"></pre>' +
                    '<button class="btn btn-default" id="IA_preview_copy" style="float: right; position:relative;bottom:55px; right:5px "> <i class="material-icons">content_copy</i> Copy</button>' +
                    '<br/>' +
                    'Saved annotation "<b>' + saveRes[0]["name"] + '" </b><div id="IA_toWebsite" style="display: inline"></div> at: <a target="_blank" id="IA_annUrl" href="' + annUrl + '">' + annUrl + '</a> <br/><br/>' +
                    '<button class="btn btn-default" style="margin:0; padding: 2px; text-transform:none; font-weight:normal" id="IA_JS_inject"><span class="caret"/>  How do i get this annotation into my website?</button>' +
                    '<br/><br/><br/>' +
                    '<div id="IA_loginSection">' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>'
                );
                $('#IA_preview_copy').click(function () {
                    copyStr(JSON.stringify(resp.jsonLd, null, 2));
                });

                $('#IA_JS_inject').after(
                    '<div id="IA_JS_inject_area" style="display:none;">' +
                    '<br/> Add this Javascript code to your Website: ' +
                    '<pre id="IA_JS_inject_code"></pre>' +
                    '<button class="btn btn-default" id="IA_inject_copy" style="float: right; position:relative;bottom:55px; right:5px "> <i class="material-icons">content_copy</i> Copy</button>' +
                    '</div>'
                );
                var injectCode = createInjectionCodeForURL(saveRes[0]["UID"]);
                $('#IA_inject_copy').click(function () {
                    copyStr(injectCode);
                });
                $('#IA_JS_inject_code').html(injectCode);

                $('#IA_JS_inject').click(function () {
                    if ($('#IA_JS_inject_area').html()) {
                        if ($('#IA_JS_inject_area').css('display') === 'none') {
                            $('#IA_JS_inject_area').slideDown(200);
                        }
                        else {
                            $('#IA_JS_inject_area').slideUp(200);
                        }
                    }
                });

                $('#IA_preview_textArea').html(syntaxHighlight(JSON.stringify(resp.jsonLd, null, 2)));
                var iasemantify_addWebsites = function () {
                    httpGetHeaders(semantifyUrl + "/api/website", {'Authorization': 'Bearer ' + semantifyToken}, function (websiteRes) {
                        if (websiteRes) {
                            $('#IA_loginSection').after('<div class="list-group" id="IA_my_websites"><h4>Your websites: (Select one to save your annotation to) </h4> </div>');
                            websiteRes.forEach(function (ele) {
                                $('#IA_my_websites').append('<button type="button" class="list-group-item list-group-item-action" id="IA_' + ele["apiKey"] + '" style="padding: 5px 0">' + ele["name"] + ' (' + ele["domain"] + ')' + '</button>');
                                $('#IA_' + ele["apiKey"]).click(function () {
                                    $('#IA_my_websites').slideUp(100);
                                    httpPostJson(semantifyUrl + "/api/annotation/" + ele["apiKey"], bulk, function (newSaveRes) {
                                        if (newSaveRes) {
                                            snackBarOptions["content"] = 'Saved the annotation to: ' + ele["name"] + ' (' + ele["domain"] + ')';
                                            $.snackbar(snackBarOptions);
                                            $('#IA_toWebsite').append('to website <b>' + ele["name"] + (ele["domain"] ? ' (' + ele["domain"] + ')' : '') + '</b>');
                                            var newUrl = semantifyShortUrl + newSaveRes[0]["UID"];
                                            $('#IA_annUrl').html(newUrl).attr("href", newUrl);
                                            var newInjectCode = createInjectionCodeForURL(newSaveRes[0]["UID"]);
                                            $('#IA_inject_copy').click(function () {
                                                copyStr(newInjectCode);
                                            });
                                            $('#IA_JS_inject_code').html(newInjectCode);
                                        }
                                        else {
                                            snackBarOptions["content"] = 'Failed to save the annotation to: ' + ele["name"] + ' (' + ele["domain"] + ')';
                                            $.snackbar(snackBarOptions);
                                        }
                                    });

                                });
                            });
                        }
                        else {
                            snackBarOptions["content"] = "There has been an error when retrieving your websites";
                            $.snackbar(snackBarOptions);
                        }
                    });
                };
                //var str = createInjectionCodeForURL(UID);

                if (!semantifyToken) {
                    $('#IA_loginSection').append(
                        '<p>Want to save this Annotation to your Semantify.it account?</p>' +
                        '<button type="button" class="btn button-sti-red" id="IA_loginBtn" style="margin:0 10px 0 0">Login</button>' +
                        //style on login btn is because the icon makes the button larger
                        '<button type="button" class="btn button-sti-red" id="IA_registerBtn" style="padding:6px 30px" onclick=" window.open(\'https://semantify.it/register\',\'_blank\')" title="Register at semantify.it"> <i class="material-icons">open_in_new</i>  Register</button>' +
                        '<div id="IA_credentialsSection" hidden>' +
                        '<input type="text" class="form-control" id="IA_username" placeholder="Username/Email" title="Username/Email">' +
                        '<input type="password" class="form-control" id="IA_password" placeholder="Password" title="Password">' +
                        '</div>'
                    );
                }
                else {
                     iasemantify_addWebsites();
                }

                var loginOnEnter = function (event) {
                    if (event.keyCode === 13) {
                        $("#IA_loginBtn").click();
                    }
                };

                $("#IA_username").keyup(function (event) {
                    loginOnEnter(event);
                });
                $("#IA_password").keyup(function (event) {
                    loginOnEnter(event);
                });

                $('#IA_saveModal')
                    .modal()
                    .on('hidden.bs.modal', function () {
                        $(this).remove();
                    });

                $('#IA_loginBtn').click(function () {
                    if ($('#IA_credentialsSection').css('display') === 'none') {
                        $('#IA_credentialsSection').slideDown(100);
                    }
                    else {
                        var credentials = {
                            identifier: $('#IA_username').val(),
                            password: $('#IA_password').val()
                        };

                        httpPostJson(semantifyUrl + "/api/login", credentials, function (loginResp) {
                            if (loginResp) {
                                $('#IA_loginSection').slideUp(100);
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
            else {
                snackBarOptions["content"] = "Successfully saved Annotation to semantify.it";
                $.snackbar(snackBarOptions);
            }
        });
    }
};
var previewBtn = {
    "name": "Preview",
    "icon": "find_in_page",
    "createJsonLD": true,
    "onclick": function (resp) {
        if (resp.jsonLd === null) {
            return;
        }
        var dummy = document.createElement("div");
        document.body.appendChild(dummy);
        dummy.setAttribute("id", "preview_id");
        $('#preview_id').append(
            '<div class="bootstrap semantify">' +
            '<div class="modal fade" id="previewModal" role="dialog">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
            '<h4 class="modal-title">Preview JSON-LD</h4>' +
            '</div>' +
            '<div class="modal-body">' +
            '<pre id="preview_textArea" style="max-height: 500px;"></pre>' +
            '<button class="btn btn-default" id="IA_simple_preview_copy" style="float: right; position:relative;bottom:55px; right:5px "> <i class="material-icons">content_copy</i> Copy</button>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
        $('#preview_textArea').html(syntaxHighlight(JSON.stringify(resp.jsonLd, null, 2)));
        $('#previewModal')
            .modal()
            .on('hidden.bs.modal', function () {
                $(this).remove();
            });
        $('#IA_simple_preview_copy').click(function () {
            copyStr(JSON.stringify(resp.jsonLd, null, 2));
        });

    }
};

var defaultBtns = [clearBtn, saveBtn];

var wpDefaultBtns = [previewBtn, wordPressSaveBtn, wordPressDeleteBtn];


IAsemantify_Init();

function IAsemantify_Init() {
    if (!iasemantify_sdoPropertiesReady)
        getPropertiesJson();
    if (!iasemantify_sdoClassesReady)
        getClassesJson();

    $('.IA_Box').each(function () {
        if ($(this).data('init') === "true")
            return;
        $(this).data('init', "true");
        var dsId = $(this).data("dsid");
        var dsHash = $(this).data("dshash");
        var dsName = $(this).data("dsname");
        var buttonsChoice = $(this).data("btns");
        var sub = $(this).data("sub") || true;
        var title = $(this).data("title");

        var buttons = getButtons(buttonsChoice);

        $(this).children('div').each(function () {
            if ($(this).hasClass('IA_Btn')) {
                var button = {};
                button["name"] = $(this).data("name");
                button["icon"] = $(this).data("icon");
                button["onlyIcon"] = $(this).data("onlyIcon");
                button["createJsonLD"] = !!$(this).data("createjsonld");
                button["onclick"] = window[$(this).data("onclick")];
                buttons.push(button);
            }
        });

        $(this).append(
            '<div id="loading' + iasemantify_panelId + '" class="col-lg-3 col-md-4 col-sm-6 text-center" style="margin: 10px; padding: 10px; background: white; border-radius: 10px;">' +
            '<img src="' + semantifyUrl + '/images/loading.gif">' +
            '</div>'
        );

        (function (id, $jqueryElement) {
            if (dsId) {
                httpGet(semantifyUrl + "/api/domainSpecification/" + dsId, function (ds) {
                    ds["hash"] = null;
                    addBox($jqueryElement, id, ds, buttons, sub, title, null);
                });
            }
            else if (dsHash) {
                httpGet(semantifyUrl + "/api/domainSpecification/hash/" + dsHash, function (ds) {
                    ds["hash"] = dsHash;
                    addBox($jqueryElement, id, ds, buttons, sub, title, null);
                });
            }
            else if (dsName) {
                httpGet(semantifyUrl + "/api/domainSpecification/searchName/" + dsName, function (dsList) {
                    var ds = dsList[0];
                    ds["hash"] = null;
                    addBox($jqueryElement, id, ds, buttons, sub, title, null);
                });
            }
        }(iasemantify_panelId, $(this), sub));

        iasemantify_panelCount++;
        iasemantify_panelId = "IAsemantifyPanel" + iasemantify_panelCount;

    });
}

function getButtons(btnString) {
    var buttons = [];
    switch (btnString) {
        case "no" :
            buttons = [];
            break;
        case "default":
        case undefined:
        case null:
            buttons = defaultBtns.slice(); //to pass by value and not reference
            break;
        case "wp_delete":
            buttons = [wordPressDeleteBtn];
            break;
        case "wp_default":
            buttons = wpDefaultBtns.slice(); //to pass by value and not reference
            break;
        default:
            var buttonsArray = btnString.split("+");
            buttonsArray.forEach(function (b) {
                switch (b) {
                    case "preview":
                        buttons.push(previewBtn);
                        break;
                    case "clear":
                        buttons.push(clearBtn);
                        break;
                    case "delete":
                        buttons.push(deleteBtn);
                        break;
                    case "save":
                        buttons.push(saveBtn);
                        break;
                    case "copy":
                        buttons.push(copyBtn);
                        break;
                    case "wpsave":
                        buttons.push(wordPressSaveBtn);
                        break;
                }
            });
    }
    return buttons;
}

function getPropertiesJson() {
    httpGet("https://semantify.it/assets/data/latest/sdo_properties.json", function (data) {
        iasemantify_sdoProperties = data;
        iasemantify_sdoPropertiesReady = true;
    });
}

function getClassesJson() {
    httpGet("https://semantify.it/assets/data/latest/sdo_classes.json", function (data) {
        iasemantify_sdoClasses = data;
        iasemantify_sdoClassesReady = true;
    });
}

function getAllInputs(panelId) {
    var allInputs = [];
    inputFields.forEach(function (a) {
        var compareId = a.slice(a.indexOf("_") + 1, a.indexOf("_", a.indexOf("_") + 1));
        if (compareId === panelId.toString()) { //only inputs from same panel
            allInputs.push(a);
        }
    });
    return allInputs;
}

function fillBox(panelId, UID, web_id, web_secret, hasDS) {
    $('#panel-' + panelId)
        .data("smtfyAnnId", UID)
        .data("smtfyWebId", web_id)
        .data("smtfyWebSecret", web_secret);
    var allInputs = getAllInputs(panelId);
    httpGet(semantifyUrl + "/api/annotation/short/" + UID, function (data) {
        if (typeof data === "string") {
            data = JSON.parse(data);
        }
        if (data === undefined) {
            $('#panel-body-' + panelId).html("There was an error loading the annotation with id: <b>" + UID + "</b> from the server!\n Reload the page or check if the annotation was deleted");
            $("#panel-footer-btn-Preview-" + panelId).prop("disabled",true);
            $("#panel-footer-btn-Save-" + panelId).prop("disabled",true);
        } else {
            if(containsArray(data)){
                $("#panel-body-" + panelId).html('<pre id="ia_panel_' + panelId + '_content"></pre> To edit this annotation, please visit <a href="https://semantify.it">semantify.it</a>  and use the full editor. In this version, no multiple values are allowed! ');
                $("#panel-footer-btn-Preview-" + panelId).prop("already_annotation_created",data);
                $("#panel-footer-btn-Save-" + panelId).prop("disabled",true);

                var code=$("#ia_panel_" + panelId + "_content");
                code.html(syntaxHighlight(JSON.stringify(data, null, 2)));
                code.css("max-height", "200px");
                code.css("overflow", "hidden");
                code.css("text-overflow", "ellipsis");
                code.css("-webkit-mask", "linear-gradient(0deg, rgba(0,0,0,0) 20%, rgba(0,0,0,1) 70%)");

            }else{
                if (hasDS) {
                    var flatJson = flatten(data);
                    $('#sub_' + panelId).val(flatJson['@type']).change();
                    allInputs.forEach(function (a) {
                        var $inputField = $("#" + a);
                        var path = $inputField.data("name");
                        var tempValue = flatJson[path.replace(/-/g, ".")];
                        if(tempValue!==undefined && tempValue.length>0){
                            tempValue=tempValue.replace('http://schema.org/','');
                        }
                        $inputField.val(tempValue);
                    });
                } else {
                    $("#panel-body-" + panelId).append('This annotation does not have any Domain Specification. Therefore you won`t be able to edit it.');
                    $("#panel-footer-btn-Preview-" + panelId).prop("already_annotation_created",data);
                    $("#panel-footer-btn-Save-" + panelId).prop("disabled",true);

                    var code=$("#ia_panel_" + panelId + "_content");
                    code.html(syntaxHighlight(JSON.stringify(data, null, 2)));
                    code.css("max-height", "200px");
                    code.css("overflow", "hidden");
                    code.css("text-overflow", "ellipsis");
                    code.css("-webkit-mask", "linear-gradient(0deg, rgba(0,0,0,0) 20%, rgba(0,0,0,1) 70%)");


                }
            }

        }
    });
}

function containsArray(obj) {
    var queue = [obj],
        found = false;

    while (!found && queue.length) {

        var o = queue.shift();

        found = Object.keys(o).some(function (k) {
            if (o[k] instanceof Array)
                return true;

            if (o[k] !== null && typeof o[k] === 'object')
                queue.push(o[k]);
        });

    }
    return found;
}

function flatten(o) {
    var prefix = arguments[1] || "", out = arguments[2] || {}, name;
    for (name in o) {
        if (o.hasOwnProperty(name)) {
            typeof o[name] === "object" ? flatten(o[name], prefix + name + '.', out) :
                out[prefix + name] = o[name];
        }
    }
    return out;
}

function helperRemove(str) {
    if (str.indexOf(':') != -1) {
        return str.substr(str.indexOf(':') + 1);

    } else {
        return str;
    }

}


function addQuickBox($jqueryElement, strbuttons, sub, panelstr, ds, title, cb) {
    var myPanelId = panelstr;
    var buttons = getButtons(strbuttons);
    addBox($jqueryElement, myPanelId, ds, buttons, sub, title, cb);
}

function addBox($jqueryElement, myPanelId, ds, buttons, sub, title, cb) {
    if (!(iasemantify_sdoPropertiesReady) || !(iasemantify_sdoClassesReady)) {
        setTimeout(function () {
            addBox($jqueryElement, myPanelId, ds, buttons, sub, title, cb);
        }, 100);
        return;
    }

    $('#loading' + myPanelId).hide();

    var curDs = ds && ds['content']["@graph"][0];
    var displayTitle = (title ? title : (curDs === undefined ? "DS not found" : curDs["schema:name"]));
    var dsName = displayTitle;
    var footer = (buttons && buttons.length > 0 ? '<div class="panel-footer text-center" id="panel-footer-' + myPanelId + '"></div>' : '');      //only display footer if there are some buttons
    $jqueryElement.append(
        '<div class="' + colClass + '" id="panel-' + myPanelId + '">' +
        '<div class="panel panel-info ">' +
        '<div class="panel-heading sti-red"> ' +
        '<h3>' + displayTitle + '</h3>' +
        '</div> ' +
        '<div class="panel-body" id="panel-body-' + myPanelId + '">' +
        '</div>' +
        footer +
        '</div>' +
        '</div>');

    if (ds) {
        var dsType = removeNS(curDs["sh:targetClass"]);
        var t = {
            "panelId": myPanelId,
            "name": dsName,
            "root": dsType
        };

        panelRoots.push(t);
        var dsProps = curDs["sh:property"];
        var req_props = [];
        var opt_props = [];
        var props = getProps(dsProps, "", dsType, myPanelId, false);

        props.forEach(function (prop) {
            if (!prop["isOptional"] && !prop["rootIsOptional"]) {
                req_props.push(prop)
            }
            else {
                opt_props.push(prop)
            }
        });

        req_props.forEach(function (p) {
            insertInputField(myPanelId, p["name"], getDesc(p["simpleName"]), p["type"], p["enums"], "#panel-body-", p["isOptional"], p["rootIsOptional"], p["multipleValuesAllowed"])
        });

        if (opt_props.length > 0) {
            $('#' + 'panel-body-' + myPanelId)
                .append('<button type="button" class="btn btn-block btn-default text-left" id="panel-body-opt-btn-' + myPanelId + '" style="background-color: lightgrey;">Optional<span class="caret"></button>')
                .append('<div id="panel-body-opt-' + myPanelId + '"> </div>');

            // this is because if you call onclick it would use the last recent panelId and not the current one
            (function (arg) {
                $('#' + 'panel-body-opt-btn-' + arg).click(function () {
                    var optionalContainer = $('#panel-body-opt-' + arg);
                    if (optionalContainer.css('display') === 'none') {
                        optionalContainer.slideDown(500);
                    }
                    else {
                        optionalContainer.slideUp(500);
                    }
                });
            })(myPanelId);

            opt_props.forEach(function (p) {
                insertInputField(myPanelId, p["name"], getDesc(p["simpleName"]), p["type"], p["enums"], "#panel-body-opt-", p["isOptional"], p["rootIsOptional"], p["multipleValuesAllowed"])
            });

            $('#panel-body-opt-' + myPanelId).slideUp(0);
            if (sub === true) {
                var subClasses = getAllSubClasses(dsType).sort();
                $("#panel-body-" + myPanelId).append('<select name="select" class="form-control input-myBackground input-mySelect" id="' + "sub_" + myPanelId + '" title="Select a sub-class if you want to specify further">');
                var dropdown = $('#' + 'sub_' + myPanelId);
                dropdown.append('<option value="' + dsType + '">Default: ' + dsType + '</option>');
                subClasses.forEach(function (e) {
                    dropdown.append('<option value="' + e + '">' + e + '</option>');
                });
                dropdown.append('</select>');
            }
        }
    } else {
        $("#panel-body-" + myPanelId).html('<pre  style="max-height:400px" id="ia_panel_' + myPanelId + '_content"></pre>');
    }

    for (var j in buttons) {
        if (buttons.hasOwnProperty(j)) {
            (function (thisPanelId) {    // because the onclick changes with each loop all buttons would call the same function
                var name = buttons[j]["name"];
                var onclick = buttons[j]["onclick"];
                var additionalClasses = buttons[j]["additionalClasses"];
                var icon = buttons[j].hasOwnProperty("icon") ? buttons[j]["icon"] : null;
                var createJsonLD = !!buttons[j]["createJsonLD"];    // default is false
                var onlyIcon = buttons[j]["onlyIcon"] !== false;    //default is true

                $('#panel-footer-' + thisPanelId).append(
                    '<button class="btn button-sti-red" id="panel-footer-btn-' + name + '-' + thisPanelId + '" style="margin: 5px 5px; padding: 10px 10px" ' + (additionalClasses ? additionalClasses : "") + ' title="' + name + '" >' +
                    (icon ? '<i class="material-icons">' + icon + '</i>' : name) +
                    (onlyIcon ? '' : ' ' + name) +
                    '</button>'
                );

                $('#panel-footer-btn-' + name + '-' + thisPanelId)
                    .click(function (e) {
                        e.preventDefault();
                        onclick({
                            "jsonLd": $("#panel-footer-btn-Preview-" + thisPanelId).prop("already_annotation_created") ? $("#panel-footer-btn-Preview-" + thisPanelId).prop("already_annotation_created") :( createJsonLD ? semantifyCreateJsonLd(thisPanelId) : null),
                            "dsHash": ds && ds["hash"],
                            "annId": $('#panel-' + thisPanelId).data("smtfyAnnId"),
                            "webId": $('#panel-' + thisPanelId).data("smtfyWebId"),
                            "panelId": thisPanelId
                        });
                    });

            })(myPanelId);
        }
    }
    if (cb)
        cb();
}


function insertInputField(panelId, name, desc, type, enumerations, panel, optional, rootIsOptional, multipleValuesAllowed) {
    var id = "IA_" + panelId + "_" + name;
    var temp = false;
    if (rootIsOptional && !optional) {
        temp = true;
    }
    switch (type) {
        case "xsd:string":
        case "xsd:anyURI":
        case "xsd:double":
        case "xsd:float":
        case "xsd:integer":
            $(panel + panelId).append('<input type="text" class="form-control input-myBackground" id="' + id + '" placeholder="' + name + '" title="' + desc + '">');
            break;
        case "xsd:boolean":
            $(panel + panelId).append('<input type="checkbox" class="form-control input-myBackground" id="' + id + '" placeholder="' + name + '" title="' + desc + '"><label for=' + id + '>' + name + '</label>');
            $("#" + id)
                .val("false")
                .on('change', function () {
                    if ($(this).is(':checked')) {
                        $(this).attr('value', 'true');
                    } else {
                        $(this).attr('value', 'false');
                    }
                });
            break;
        case "xsd:date":
            $(panel + panelId).append('<input type="text" class="form-control input-myBackground" id="' + id + '" placeholder="' + name + '" title="' + desc + '">');
            $('#' + id).datetimepicker({
                format: 'YYYY-MM-DD'
            });
            break;
        case "xsd:dateTime":
            $(panel + panelId).append('<input type="text" class="form-control input-myBackground" id="' + id + '" placeholder="' + name + '" title="' + desc + '">');
            $('#' + id).datetimepicker({
                format: 'YYYY-MM-DDTHH:mm'
            });
            break;
        case "xsd:time":
            $(panel + panelId).append('<input type="text" class="form-control input-myBackground" id="' + id + '" placeholder="' + name + '" title="' + desc + '">');
            $('#' + id).datetimepicker({
                format: 'HH:mm'
            });
            break;
        case "Enumeration":
            if (multipleValuesAllowed) {
                $(panel + panelId).append('<select multiple name="select" class="form-control input-myBackground input-mySelect" id="' + id + '" title=" ' + "You can add more than one value by pressing *Ctrl* \n\n" + desc + '">');
            } else {
                $(panel + panelId).append('<select name="select" class="form-control input-myBackground" id="' + id + '" title=" ' + desc + '">');
            }
            var enumField = $('#' + id);
            enumField.append('<option value="" disabled selected>Select: ' + name + '</option>');
            enumerations.forEach(function (e) {
                enumField.append('<option value="' + e + '">' + e + '</option>');
            });
            enumField.append('</select>');
            break;
    }
    $("#" + id)
        .data("type", type)
        .data("enumerations", enumerations)
        .data("isOptional", optional)
        .data("rootIsOptional", rootIsOptional)
        .data("multipleValuesAllowed", multipleValuesAllowed)
        .data("name", name);
    inputFields.push(id);
}

function getDesc(propertyName) {
    return iasemantify_sdoProperties[propertyName]
        ? stripHtml(iasemantify_sdoProperties[propertyName]["description"])
        : "No description available";
}

function stripHtml(html) {
    var tmp = document.createElement('DIV');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

function getProps(props, level, fatherType, myPanelId, fatherIsOptional) {
    var propList = [];
    for (var p in props) {
        if (!props.hasOwnProperty(p)) continue;
        var prop = props[p];
        var range = prop['sh:or']['@list'][0];
        var isOptional = prop["sh:minCount"] ? prop["sh:minCount"] === 0 : true;
        var name = removeNS(prop["sh:path"]);
        if (!range['sh:node']) {
            var simpleProp = {
                "simpleName": name,
                "name": (level === "" ? "" : level + "-") + name,
                "type": range["sh:datatype"],
                "fatherType": fatherType,
                "isOptional": isOptional,
                "multipleValuesAllowed": false, // only used for select enum, deprecate for now
                "rootIsOptional": fatherIsOptional
            };

            if (range['sh:in']) {
                simpleProp["type"] = "Enumeration";
                var enums = [];
                range['sh:in']['@list'].forEach(function (ele) {
                    enums.push(removeNS(ele));
                });
                simpleProp["enums"] = enums;
            }

            propList.push(simpleProp);
        }
        else {
            var myLevel = level === "" ? name : level + "-" + name;
            var path = myLevel + "-@type";
            var pathType = {
                "name": removeNS(range["sh:class"]),
                "path": path,
                "panelId": myPanelId
            };
            typeList.push(pathType);
            var fIsOptional = fatherIsOptional === true || isOptional;
            propList = propList.concat(
                getProps(range["sh:node"]["sh:property"],
                    myLevel,
                    range["sh:class"],
                    myPanelId,
                    fIsOptional));
        }
    }
    return propList;
}

function semantifyCreateJsonLd(id) {
    var dsName;
    var schemaName = "Thing";
    panelRoots.forEach(function (t) {
        if (t["panelId"] == id) {
            dsName = t["name"];
            schemaName = t["root"]
        }
    });
    var selected = $('#' + "sub_" + id).val();
    if (selected != undefined && selected != "" && selected != null) {
        schemaName = selected;
    }
    var validPaths = [];
    var allPaths = [];
    var resultJson = {
        "@context": "http://schema.org/",
        "@type": schemaName
    };
    var allRequired = true; //variable gets false if an required field is empty
    var allRequiredPaths = true; //variable gets false if an optional field is filled in that has required properties
    var allInputs = []; //all input ids from same panel
    var notFilledRequired=[]; //all inputFields that are not filled but required
    var msgs = [];

    inputFields.forEach(function (a) {
        var compareId = a.slice(a.indexOf("_") + 1, a.indexOf("_", a.indexOf("_") + 1));
        if (compareId === id.toString()) { //only inputs from same panel
            allInputs.push(a);
        }
    });

    allInputs.forEach(function (a) {
        var $inputField = $("#" + a);
        var value = $inputField.val();
        var path = $inputField.data("name");
        var optional = $inputField.data("isOptional");
        var rootOptional = $inputField.data("rootIsOptional");
        if ((value === undefined || value === null || value === "" || value.length === 0 || value.length == undefined) && (optional === false && rootOptional === false)) { //if variable is not optional but empty
            allRequired = false;
            notFilledRequired.push($inputField);
        }
        if ((value != undefined && value != null && value != "" && value.length != 0 && value.length != undefined) && rootOptional === true) {
            //check if all other paths and sub paths are filled in - else false allRequiredPaths
            var bAllPaths = [];
            var bPaths = path.split('-');
            while (bPaths.length > 1) {
                bPaths.pop();
                bAllPaths.push((bPaths.join("-")))
            }
            allInputs.forEach(function (b) {
                var $inputElem = $("#" + b);
                var bPath = $inputElem.data("name");
                var bOptional = $inputElem.data("isOptional");
                var bRootOptional = $inputElem.data("rootIsOptional");
                var len = (bPath.split("-"));
                len = len.length;
                var bValue = $inputElem.val();
                for (var z = 0; z < bAllPaths.length; z++) {
                    var len2 = bAllPaths[z].split("-");
                    len2 = len2.length;
                    if (bOptional == false && bRootOptional == true && (bPath.indexOf(bAllPaths[z]) >= 0) && len === len2 + 1) {
                        if (bValue === undefined || bValue === "" || bValue == null || bValue.length === 0 || bValue.length == undefined) {
                            msgs.push(bPath);
                            allRequiredPaths = false;
                        }
                    }
                }
            });
        }
        typeList.forEach(function (t) {
            if (t["panelId"] === id) {
                var typePath = {
                    "name": t["name"],
                    "path": t["path"]
                };
                allPaths.push(typePath)
            }
        });
        if (!(value === undefined || value === null || value === "" || value.length === 0 || value.length == undefined)) {

            var temp = path.split("-");
            while (temp.length > 1) {
                temp.pop();
                var x = temp.join("-") + "-@type";
                validPaths.push(x);
            }

            allPaths.forEach(function (a) {
                validPaths.forEach(function (v) {
                    if (v === a["path"]) {

                        resultJson = set(resultJson, a["path"], a["name"])
                    }
                });
            });

            resultJson = set(resultJson, path, value)
        }

    });
    if (allRequired && allRequiredPaths) {
        var result = (JSON.stringify(resultJson));
        return resultJson;
    } else {
        if (!allRequired) {
            //console.log(notFilledRequired);
            notFilledRequired.forEach(function(f){
                f.addClass('input-highlight');
                    setTimeout(
                        function() { f.removeClass('input-highlight'); },
                        3000
                    );
            });
            send_snackbarMSG_fail("Please fill in all required fields", 3000);
        } else {
            msgs = htmlList(unique(msgs));
            send_snackbarMSG_fail("Please also fill in <ul>" + msgs.join("") + "</ul>", 3000 + (msgs.length - 1) * 1000);

            msgs.forEach(function(n){
                var g=$('#IA_'+id+'_'+n.replace(/<(?:.|\n)*?>/gm, ''));
                g.addClass('input-highlight');
                setTimeout(
                    function() { g.removeClass('input-highlight'); },
                    3000
                );
            });
        }
        return null;
    }
}

function createInjectionCodeForURL(UID) {
    var code = "function appendAnnotation() {\n" +
        "\tvar element = document.createElement('script');\n" +
        "\telement.type = 'application/ld+json';\n" +
        "\telement.text = this.responseText;\n" +
        "\tdocument.querySelector('head').appendChild(element);\n" +
        "}\n" +
        "var request = new XMLHttpRequest();\n" +
        "request.onload = appendAnnotation;\n" +
        'request.open("get", "' + semantifyShortUrl + UID + '", true);\n' +
        "request.send();";
    return code;
}

function copyStr(str) {
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.setAttribute("id", "dummy_id");
    dummy.value = str;
    dummy.select();
    document.execCommand("copy");
    document.body.removeChild(dummy);
    send_snackbarMSG("Annotation copied into your clipboard", 3000);
}

function syntaxHighlight(json) {
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

function getAllSubClasses(base) {
    var subClasses = iasemantify_sdoClasses[base].subClasses;
    subClasses.forEach(function(c) {
        subClasses = subClasses.concat(getAllSubClasses(c));
    });
    subClasses.push(base);
    return unique(subClasses);
}

function removeNS(str) {
    return str.split(':')[1];
}

function unique(list) {
    var result = [];
    $.each(list, function (i, e) {
        if ($.inArray(e, result) == -1) result.push(e);
    });
    return result;
}

function htmlList(list) {
    var result = [];
    $.each(list, function (i, e) {
        result.push("<li>" + e + "</li> \n");
    });
    return result;
}

function send_snackbarMSG_fail(message) {
    send_snackbarMSG(message, 5000, true);
}

function send_snackbarMSG(message, duration, withWarningBox) {

    var options = {
        content: '<table class="snackbar-table"><td><i class="material-icons snackbar-icon">info</i><span>  ' + message + '</span></td></table>', // text of the snackbar
        style: "toast",
        timeout: duration ? duration : 4000,
        htmlAllowed: true,
        onClose: function () {
        }
    };
    $.snackbar(options);

    /*if (withWarningBox) {
        $('#ia_warning_msg').html(message);
        $('#ia_warning_message_box').slideDown(100);
        setTimeout(function() {
            $('#ia_warning_message_box').slideUp(500)
        }, duration ? duration : 4000)
    }*/
}

function remove(arr, what) {
    var found = arr.indexOf(what);

    while (found !== -1) {
        arr.splice(found, 1);
        found = arr.indexOf(what);
    }
}

function set(obj, path, value) {
    var schema = obj;
    var pList = path.split('-');
    var len = pList.length;
    for (var i = 0; i < len - 1; i++) {
        var elem = pList[i];
        if (!schema[elem]) schema[elem] = {};
        schema = schema[elem];
    }
    schema[pList[len - 1]] = value;
    return obj;
}

function httpGet(url, callback) {
    httpGetHeaders(url, null, callback);
}

function httpGetHeaders(url, headers, callback) {
    $.ajax({
        type: 'GET',
        url: url,
        headers: headers,
        success: function (data) {
            callback(data);
        },
        error: function () {
            callback();
        }
    });
}

function httpPostJson(url, headers, json, cb) {
    httpCall("POST", url, 'application/json ; charset=utf-8', headers, json, cb);
}

function httpPatchJson(url, headers, json, cb) {
    httpCall("PATCH", url, 'application/json ; charset=utf-8', headers, json, cb);
}

function httpCall(method, url, contentType, headers, json, cb) {
    $.ajax({
        type: method,
        contentType: contentType,
        headers: headers,
        url: url,
        data: JSON.stringify(json),
        success: function (data) {
            cb(data);
        },
        error: function (data) {
            cb(data);
        }
    });
}
