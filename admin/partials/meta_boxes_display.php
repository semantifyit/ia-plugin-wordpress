<?php
$nonce = wp_create_nonce( "my_user_vote_nonce" );
echo '<div id="ia-data" data-nonce="' . $nonce . '" data-post_id="' . $post->ID . '"></div>';
$options                = get_option( $this->plugin_name );
$websiteUID             = $options['websiteUID'];
$websiteSecret          = $options['websiteSecret'];
$phpIsChecked           = 'false';
$IA_semantify_url_route = '';

if ( get_option( 'iasemantify_setting_url_injection' ) == 'true' ) {
	global $IA_semantify_url_route;

	$current_url = get_permalink( $post->ID );

	$current_encoded_url    = str_replace( '/', '%2F', $current_url );
	$path                   = 'https://semantify.it/api/annotation/url/';
	$resultPath             = $path . $current_encoded_url;
	$theBody                = wp_remote_retrieve_body( wp_remote_get( $resultPath ) );
	$IA_semantify_url_route = $resultPath;
	$phpIsChecked           = 'true';
}

?>

<script type="text/javascript">
    var IA_injection_is_checked = "<?php Print( $phpIsChecked ); ?>";
    var IA_semantify_url_route_js = "<?php Print( $IA_semantify_url_route ); ?>";
</script>

<style>
    .check {
        margin-top: -20px;
    }

    span.check[disabled] {
        color: grey;
        background-color: grey;
        opacity: 0.5;
        cursor: not-allowed;
    }

    .inputBorder {
        border-radius: 40px;
    }

    .disabled_span {
        color: grey;
        background-color: grey;
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<div class="bootstrap semantify" id="ia_global_bootstrap_div">
    <div id="ia_warning_message_box" hidden>
        <div class="alert alert-danger" role="alert">
            <h4 id="ia_warning_msg"></h4>
        </div>
    </div>
    <div id="ia_injection_box"></div>
    <select title="choose a type" onchange="onChangeSelect(this);" name="box_select" id="select_type"
            style="background-color: white !important; min-width:150px "></select>
    <button style="margin-left:10px;padding:5px 20px" title="Please select a type first!"
            class="btn btn-sm button-sti-red" id="addPane" disabled="true" type="button"><i class="material-icons">note_add</i>
    </button>
    <div class="btn-group pull-right">
        <button type="button" class="btn btn-sm button-sti-red pull-right dropdown-toggle" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
            <i class="material-icons">menu</i>
        </button>
        <ul class="dropdown-menu">
            <li><a href="#" id="ia_menu_report">Report</a></li>
            <li><a href="#" id="ia_menu_help">Help</a></li>
        </ul>
    </div>
    <button style="margin-right:10px;padding:5px 20px" title="Import from your semantify account"
            class="btn btn-sm button-sti-red pull-right" id="ia_menu_add_ann" type="button"><i class="material-icons"
                                                                                               style="transform: rotate(90deg);">exit_to_app</i>
    </button>
    <br/>
    <hr>
    <div class="container-fluid">
        <div class="inside" id="inside">
            <div class="row" id="row">
            </div>
        </div>
    </div>
</div>

<script>
    var IA_delete_id_whitelist = [];
    $('#ia_warning_message_box').slideUp();
    if (IA_injection_is_checked === 'true') {
        var button = '<button id="IA_view_inserted_annotations" type="button" class="btn btn-sm btn-danger">View annotations <div id=IA_loading_url></div></button>'
        $('#ia_injection_box').append('<div class="alert alert-warning" role="alert">Additionally to the annotaitons you added manually, you enabled the "auto-annotation-lookup" feature that might add more annotations to your website!<br>' + button + '</div>');
        $('#IA_view_inserted_annotations').click(function () {
            $('#IA_loading_url').html('<img style="max-height:20px" src="https://semantify.it/images/loading.gif">');
            $.get(IA_semantify_url_route_js, function (data) {

            })
                .done(function (data) {
                    $('#IA_loading_url').html('');
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
                    $('#preview_textArea').html(InstantAnnotation.util.syntaxHighlight(JSON.stringify(data, null, 2)));
                    $('#previewModal')
                        .modal()
                        .on('hidden.bs.modal', function () {
                            $(this).remove();
                        });
                    $('#IA_simple_preview_copy').click(function () {
                        InstantAnnotation.util.copyStr(JSON.stringify(data, null, 2));
                    });

                })
                .fail(function (err) {
                    $('#IA_loading_url').html('');
                    if (err.statusText == "Not Found") {
                        InstantAnnotation.util.send_snackbarMSG_fail("No annotations found for this url ");
                    } else {
                        InstantAnnotation.util.send_snackbarMSG_fail("Oops, an error occurred.");
                    }
                })
        });
    }

    function onChangeSelect(element) {
        $('#addPane').prop("disabled", false);
        $('#addPane').prop("title", "Add a new annotation box");
    }

    var IA_dashboard_annotation_store = null;
    var IA_currently_added_annotations = [];

    // add select options
    function SortByName(a, b) {
        var aName = a.name.toLowerCase();
        var bName = b.name.toLowerCase();
        return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    }

    var websiteUID = <?php echo json_encode( $websiteUID );?>;
    var websiteSecret = <?php echo json_encode( $websiteSecret );?>;
    //console.log(websiteUID);

    InstantAnnotation.util.httpGet(InstantAnnotation.util.semantifyUrl + "/api/domainSpecification/instantAnnotation", function (ds) {
        if (!ds) {
            return;
        }
        ds.sort(SortByName);
        $('#select_type').append('<option hidden>Select type</option>');
        $('#select_type').append('<optgroup label="Public template"/>');
        ds.forEach(function (d) {
            $('#select_type').append($('<option>', {
                value: d.hash,
                text: d.name
            }));
        });
        $('#select_type').append('</optgroup>');

        if (websiteUID && websiteUID !== 'DEFAULT') {
            InstantAnnotation.util.httpGet(InstantAnnotation.util.semantifyUrl + "/api/website/" + websiteUID + "/domainspecification/", function (ds) {
                //console.log(ds);
                if (!ds) {
                    return;
                }
                ds.sort(SortByName);

                var personalDSHtml ='<option hidden>Select type</option>'+'<optgroup label="Private template"/>';
                ds.forEach(function (d) {
                    personalDSHtml += '<option value="' + d.hash + '">' +
                        d.name +
                        '</option>';
                });
                personalDSHtml += '</optgroup>';
                personalDSHtml += '<option disabled style="font-style: italic">no private templates</option>';
                $('#select_type').prepend(personalDSHtml);
                $('#select_type').prop("selectedIndex", 0);
                checkCustomPostType();
            });
        }else{
            checkCustomPostType();
        }

    });

    // rest

    iasi_saveWebsiteUID = <?php echo json_encode( $websiteUID );?>;
    iasi_saveWebsiteSecret = <?php echo json_encode( $websiteSecret );?>;

    var existingAnnotationsString = <?php echo json_encode( get_post_meta( get_the_ID(), $this->plugin_name . "_ann_id", true ) );?>;
    var existingAnnotationsArray = existingAnnotationsString.split(',');    //shift because string starts with ','
    existingAnnotationsArray.shift();

    //console.log(existingAnnotationsString);

    function checkCustomPostType(){
        var postType;
        postType = <?php echo json_encode(get_post_type());?>;
        if(postType !== "post" && postType !== "page"){
            console.log('Found custom post type: '+postType);
            var options=$('#select_type')[0].options;
            for (var i = 0; i < options.length; i++) {
                if(!options[i].disabled && !options[i].hidden){
                    if(options[i].innerText.toLowerCase().indexOf(postType)!==-1){
                       $('#select_type')[0].options[i].selected=true;
                       $('#addPane')[0].disabled=false;
                       break;
                    }

                };
            };
        }
    }
    function ia_addNewBox(ann_id, ds_id, web_id, web_secret) {

        let options={
            buttons: 'wp_default',
            smtfyAnnotationUID: ann_id,
            smtfySemantifyWebsiteSecret:web_secret,
            smtfySemantifyWebsiteUID:web_id
        }

        InstantAnnotation.createIABox('row', ds_id == 'NO_DS' ? null : ds_id, options, (box) => {});
    }

    existingAnnotationsArray.forEach(function (idStr) {
        var splits = idStr.split(';');
        var ann_id = splits[0];
        var ds_id = splits[1];
        var web_id = splits[2];
        var web_secret = splits[3];
        ia_addNewBox(ann_id, ds_id, web_id, web_secret);
    });

    $("#addPane").click(function () {
        var id = $("#select_type").val();
        if (!id) {
            InstantAnnotation.util.send_snackbarMSG("Please select a type first!")
            return;
        }
        InstantAnnotation.util.httpGet(InstantAnnotation.util.semantifyUrl + "/api/domainSpecification/hash/" + id, function (ds) {
            ds["hash"] = id;
            let options={
                buttons: 'wp_default',
            }
             InstantAnnotation.createIABox('row', id, options,  (box) => {});
        });
    });

    // adding button click listeners
    $("#ia_menu_report").click(function (e) {
        e.preventDefault();
        var dummy = document.createElement("div");
        document.body.appendChild(dummy);
        dummy.setAttribute("id", "ia_menu_report_id");
        $('#ia_menu_report_id').append(
            '<div class="bootstrap semantify">' +
            '<div class="modal fade" id="ia_menu_reportModal" role="dialog">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
            '<h4 class="modal-title">Report</h4>' +
            '</div>' +
            '<div id="ia_menu_report_body" class="modal-body">' +
            '<p>Something wrong? Send us a message and make sure to leave some contact information if you expect a reply!</p>' +
            '<textarea class="form-control" style="min-width: 100%" id="ia_menu_report_text" placeholder="Type in your message you\'d like to submit to us"></textarea>' +
            '<button class="btn btn-sm button-sti-red" id="ia_menu_report_submit" type="button">Send</button>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" id="IA_close_button_modal"class="btn btn-default" data-dismiss="modal">Close</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
        $('#ia_menu_reportModal')
            .modal()
            .on('hidden.bs.modal', function () {
                $(this).remove();
            });
        $('#ia_menu_report_submit').click(function (e) {
            e.preventDefault();

            var reportText = $('#ia_menu_report_text').val();
            var postMeta = existingAnnotationsArray.map(function (value) {
                var splits = value.split(';');
                return {
                    annotation_uid: splits[0],
                    domainSpecification_hash: splits[1],
                    website_uid: splits[2],
                    website_secret: splits[3]
                }
            });
            var report = {
                text: "*--- New Report ---*\n" +
                "Origin: " + window.location.href + "\n" +
                "Date: " + new Date().toString() + "\n" +
                "Report message: \n```" + reportText + "```\n" +
                "Current website: " + websiteUID + " ( " + websiteSecret + ")\n" +
                "PostMeta: \n```" + JSON.stringify(postMeta, null, 2) + "```\n" +
                "*--- End Report ---* \n"
            };
            InstantAnnotation.util.httpCall('POST', atob(window.ia_kcalsLru).replace(/X1/g, ''), undefined, undefined, report, function (res) {
                if (res === 'ok') {
                    InstantAnnotation.util.send_snackbarMSG("Successfully sent message");
                } else {
                    InstantAnnotation.util.send_snackbarMSG_fail("Something went wrong when sending message");
                    $('#ia_menu_report_body').html(
                        '<h5>Oops, something went wrong when sending message</h5>'
                    );
                }
            });

            $('#ia_menu_report_body').html(
                '<h5>Thanks for your feedback, we\'ll try to get back to you ASAP</h5>'
            );

        })

    });

    $("#ia_menu_help").click(function (e) {
        e.preventDefault();
        var dummy = document.createElement("div");
        document.body.appendChild(dummy);
        dummy.setAttribute("id", "ia_menu_help_id");
        $('#ia_menu_help_id').append(
            '<div class="bootstrap semantify">' +
            '<div class="modal fade" id="ia_menu_helpModal" role="dialog">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
            '<h4 class="modal-title">Help</h4>' +
            '</div>' +
            '<div class="modal-body">' +
            '<p>Thanks for using Instant Annotation!</p>' +
            '<p>Please check out the info section or the "view more" section in your plugin list for a short documentation of the plugin.</p>' +
            '<p>Doesn\'t help you? Send us a message by using the "Report" button!</p>' +
            '<br/>' +
            '<p>Something wrong with Instant Annotation? Reset this Page/Post for a clean slate.</p>' +
            '<button class="btn btn-sm button-sti-red" id="ia_menu_reset_submit" type="button">Reset</button>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
        $('#ia_menu_helpModal')
            .modal()
            .on('hidden.bs.modal', function () {
                $(this).remove();
            });

        var post_id = $('#ia-data').attr("data-post_id");
        var nonce = $('#ia-data').attr("data-nonce");

        $('#ia_menu_reset_submit').click(function (e) {
            e.preventDefault();
            var result = confirm("Do you really want to reset this Page/Post for Instant Annotation (Only Instant Annotation stuff will get reset, your other data will stay)");
            if (result) {
                $.ajax({
                    type: "post",
                    url: myAjax.ajaxurl,
                    data: {
                        action: "iasemantify_reset_page",
                        post_id: post_id,
                        nonce: nonce
                    },
                    success: function () {
                        IA_delete_id_whitelist = [];
                        IA_currently_added_annotations = [];
                        existingAnnUidArr = [];
                        existingAnnotationsArray = [];
                        InstantAnnotation.util.send_snackbarMSG("Successfully Reset Page");
                        $('#ia_menu_helpModal').modal('toggle');
                        $('#row').html('');
                    },
                    error: function () {
                        InstantAnnotation.util.send_snackbarMSG_fail("An error occurred while resetting the page");
                    }
                });
            }
        });
    });

    $("#ia_menu_add_ann").click(function (e) {
        e.preventDefault();
        var dummy = document.createElement("div");
        document.body.appendChild(dummy);
        dummy.setAttribute("id", "ia_menu_add_ann_id");
        $('#ia_menu_add_ann_id').append(
            '<div class="bootstrap semantify">' +
            '<div class="modal fade" id="ia_menu_add_annModal" role="dialog">' +
            '<div class="modal-dialog">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<button type="button" class="close" data-dismiss="modal">&times;</button>' +
            '<h4 class="modal-title">Import Semantify annotations</h4>' +
            '</div>' +
            '<div id="ia_menu_add_ann_body" class="modal-body">' +
            '<p>Are you missing some annotation boxes? Or do you want to import more complex annotations you created on <a>semantify.it</a>?</p>' +
            '<p>Choose your missing annotations from the list down below! (These are all your annotations from the semantify.it website specified in your settings. Greyed out fields are already on your site.)</p>' +
            '<img style="margin-left:40%" id="loading_import_annotations" src="https://semantify.it/images/loading.gif">' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>'
        );
        $('#ia_menu_add_annModal')
            .modal()
            .on('hidden.bs.modal', function () {
                $(this).remove();
            });
        if(websiteUID==="DEFAULT"){
            InstantAnnotation.util.send_snackbarMSG_fail("To use this function you need to be logged in.");
            $('#ia_menu_add_ann_body').html(
                '<h5>Please enter your login details in the settings section to us this functionality!</h5>'
            );

        }else{
            InstantAnnotation.util.httpGet(InstantAnnotation.util.semantifyUrl + "/api/annotation/list/" + websiteUID + "?limit=0", function (res) {
                $("#loading_import_annotations").remove();
                if (res) {
                    var existingAnnUidArr = existingAnnotationsArray.map((function (value) {
                        return value.split(';')[0];
                    }));
                    var annotationsContent = '<div class="form-group" ><div id="annotatios_form_group_content" style="max-height: 200px; padding-bottom: 15px; padding-top: 15px; overflow-y: scroll;" class="row">';
                    var annotations = res.data;
                    IA_dashboard_annotation_store = annotations;
                    annotations.sort(function compare(a, b) {
                        if (a.name < b.name) {
                            return -1;
                        }
                        if (a.name > b.name) {
                            return 1;
                        }
                        return 0;
                    });

                    for (var i = 0; i < annotations.length; i++) {
                        var checked = existingAnnUidArr.includes(annotations[i].UID);
                        if (checked && IA_delete_id_whitelist.includes(annotations[i].UID)) {
                            checked = false;
                        }

                        var style = 'title="Please remove the imported annotations with the delete button on the box." style="cursor:not-allowed"';
                        if (checked === false) {
                            style = 'style="color:black"';
                        }
                        annotationsContent += '<div class="col-md-6"><div class="checkbox" style="margin: border-width:1px">' +
                            '<label id="ia_label_checkbox_' + annotations[i].UID + '"' + style + '>' +
                            '<input   id="ia_add_ann_' + annotations[i].UID + '" type="checkbox" ' + (checked && 'checked disabled') + '>' +
                            '<span class="checkbox-material">' +
                            '<span id="ia_add_ann_span_' + annotations[i].UID + '" class="check" ' + (checked && 'checked disabled') + '>' +
                            '</span>' +
                            '</span> ' +
                            annotations[i].name +
                            (annotations[i].domainSpecification ? ' (' + annotations[i].domainSpecification.name + ')' : '') +
                            '</label>' +
                            '</div>' +
                            '</div>';
                    }
                    annotationsContent += '</div></div>';
                    annotationsContent += '<button class="btn btn-md button-sti-red" id="ia_menu_add_ann_submit" type="button">Import</button>';
                    $('#ia_menu_add_ann_body').append('<input type="text" class="form-control input-myBackground" id="IA_modal_filter" placeholder="Filter" title="Filter the annotations.">');
                    $('#ia_menu_add_ann_body').append(annotationsContent);

                    $('#IA_modal_filter').keyup(function () {
                        if (IA_dashboard_annotation_store === null) {
                            InstantAnnotation.util.send_snackbarMSG_fail("An error occurred during filter.");
                            return;
                        }
                        let text = $('#IA_modal_filter').val();

                        $('#annotatios_form_group_content').html("");
                        var contentstring = "";
                        for (var i = 0; i < IA_dashboard_annotation_store.length; i++) {
                            var checked = existingAnnUidArr.includes(IA_dashboard_annotation_store[i].UID);
                            if (!checked && IA_currently_added_annotations.includes(IA_dashboard_annotation_store[i].UID)) {
                                checked = true;
                            }
                            if (checked && IA_delete_id_whitelist.includes(IA_dashboard_annotation_store[i].UID)) {
                                checked = false;
                            }
                            if (IA_dashboard_annotation_store[i].name.toLowerCase().indexOf(text.toLowerCase()) !== -1) {
                                var style = 'title="Please remove the imported annotations with the delete button on the box." style="cursor:not-allowed"';
                                if (checked === false) {
                                    style = 'style="color:black"';
                                }

                                contentstring = contentstring + '<div class="col-md-6"><div class="checkbox" style="border-width:1px">' +
                                    '<label id="ia_label_checkbox_' + annotations[i].UID + '"' + style + '>' +
                                    '<input   id="ia_add_ann_' + annotations[i].UID + '" type="checkbox" ' + (checked && 'checked disabled') + '>' +
                                    '<span class="checkbox-material">' +
                                    '<span  id="ia_add_ann_span_' + annotations[i].UID + '" class="check" ' + (checked && 'checked disabled') + '>' +
                                    '</span>' +
                                    '</span> ' +
                                    IA_dashboard_annotation_store[i].name +
                                    (IA_dashboard_annotation_store[i].domainSpecification ? ' (' + IA_dashboard_annotation_store[i].domainSpecification.name + ')' : '') +
                                    '</label>' +
                                    '</div>' +
                                    '</div>';

                            }
                        }
                        $('#annotatios_form_group_content').html(contentstring)
                    });

                    $('#ia_menu_add_ann_submit').click(function (e) {
                        e.preventDefault();

                        var savedWebsites = [];
                        var existingAnnUidArr = existingAnnotationsArray.map((function (value) {
                            return value.split(';')[0];
                        }));
                        annotations.forEach(function (ann) {
                            if ($('#ia_add_ann_' + ann.UID).is(':checked') && IA_currently_added_annotations.indexOf(ann.UID) === -1) {

                                $('#ia_add_ann_' + ann.UID).prop('disabled', true);
                                $('#ia_add_ann_span_' + ann.UID).addClass('disabled_span');

                                $('#ia_label_checkbox_' + ann.UID).prop('title', 'Please remove the imported annotations with the delete button on the box.');
                                $('#ia_label_checkbox_' + ann.UID).prop('style', 'cursor:not-allowed');

                                $('#ia_label_checkbox_' + ann.UID).css("color", "");
                                IA_currently_added_annotations.push(ann.UID);
                                if (!existingAnnUidArr.includes(ann.UID)) {
                                    existingAnnotationsArray.push(ann.UID);
                                }
                                savedWebsites.push(ann);
                            }
                        });
                        savedWebsites = savedWebsites.filter(function (value) {
                            var whiteListed = false;
                            if (IA_delete_id_whitelist.includes(value.UID)) {
                                whiteListed = true;
                                InstantAnnotation.util.remove(IA_delete_id_whitelist, value.UID);
                            }
                            return (!existingAnnUidArr.includes(value.UID) || whiteListed);
                        });
                        if (savedWebsites.length === 0) {
                            return;
                        }

                        //$('#ia_menu_add_ann_body').append(
                        //    '<br/><div id="loading_pushining_ann" class="col-lg-3 col-md-4 col-sm-6 text-center" style="margin: 10px; padding: 10px; background: white; border-radius: 10px;">' +
                        //    '<img src="' + InstantAnnotation.util.semantifyUrl + '/images/loading.gif">' +
                        //    '</div>');
                        $('#ia_menu_add_ann_submit').prop('disabled', true);

                        var post_id = $('#ia-data').attr("data-post_id");
                        var nonce = $('#ia-data').attr("data-nonce");

                        var annIds = savedWebsites
                            .map(function (value) {
                                return value.UID
                            })
                            .join(',');
                        var dsIds = savedWebsites
                            .map(function (value) {
                                return value.domainSpecification ? value.domainSpecification.hash : 'NO_DS'
                            })
                            .join(',');

                        $.ajax({
                            type: "post",
                            url: myAjax.ajaxurl,
                            data: {
                                action: "iasemantify_multi_push_ann",
                                post_id: post_id,
                                nonce: nonce,
                                ann_ids: annIds,
                                ds_hashes: dsIds,
                                web_id: websiteUID,
                                web_secret: websiteSecret
                            },
                            success: function () {
                                //$('#loading_pushining_ann').html("");
                                InstantAnnotation.util.send_snackbarMSG("Successfully Added Annotations");
                                savedWebsites.forEach(function (ann) {
                                    ia_addNewBox(ann.UID, ann.domainSpecification ? ann.domainSpecification.hash : 'NO_DS', websiteUID, websiteSecret);
                                });
                                $('#ia_menu_add_ann_submit').prop('disabled', false);
                            },
                            error: function () {
                                InstantAnnotation.util.send_snackbarMSG_fail("An error occurred while adding the annotations");
                                //$('#loading_pushining_ann').html("");
                            }
                        });
                    });

                } else {
                    InstantAnnotation.util.send_snackbarMSG_fail("Failed to fetch annotations, maybe your semantify website UID is incorrect?");
                    $('#ia_menu_add_ann_body').html(
                        '<h5>Failed to fetch annotations, maybe your semantify website UID is incorrect?</h5>'
                    );
                }

            });
        }
    });

</script>
