<?php

$nonce = wp_create_nonce( "my_user_vote_nonce" );
echo '<div id="ia-data" data-nonce="' . $nonce . '"></div>';

$ia_is_checked = 'false';
if ( get_option( 'iasemantify_setting_url_injection' ) == 'true' ) {
	$ia_is_checked = 'true';
}

function debug_to_console( $data ) {
	$output = $data;
	if ( is_array( $output ) ) {
		$output = implode( ',', $output );
	}

	echo "<script>console.log( '" . $output . "' );</script>";
}

?>

<div class="wrap">
    <div class="bootstrap semantify semantify-instant-annotations">
        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

        <form method="post" name="iasemantify_options" action="options.php" id="ia_postForm">
			<?php
			//Grab all options
			$options       = get_option( $this->plugin_name );
			$websiteUID    = $options['websiteUID'];
			$websiteSecret = $options['websiteSecret'];
			?>

			<?php
			settings_fields( $this->plugin_name );
			do_settings_sections( $this->plugin_name );
			?>

            <br/>
            <h3>Add the generated annotations to your semantify account? (optional)</h3>

            <fieldset>
                <fieldset>
                    <p>semantify.it website UID:</p>
                    <legend class="screen-reader-text">
                        <span><?php _e( 'Add your semantify website UID', $this->plugin_name ); ?></span>
                    </legend>
                    <input type="text"
                           class="regular-text"
                           style="background: white; width:25rem"
                           id="<?php echo $this->plugin_name; ?>-websiteUID"
                           name="<?php echo $this->plugin_name; ?>[websiteUID]"
                           value="<?php if ( ! empty( $websiteUID ) ) {
						       echo $websiteUID;
					       } ?>"
                    />
                </fieldset>
                <fieldset>
                    <p>semantify.it website secret:</p>
                    <legend class="screen-reader-text">
                        <span><?php _e( 'Add your semantify website secret', $this->plugin_name ); ?></span>
                    </legend>
                    <input type="text"
                           class="regular-text"
                           style="background: white; width:25rem"
                           id="<?php echo $this->plugin_name; ?>-websiteSecret"
                           name="<?php echo $this->plugin_name; ?>[websiteSecret]"
                           value="<?php if ( ! empty( $websiteSecret ) ) {
						       echo $websiteSecret;
					       } ?>"
                    />
                </fieldset>
            </fieldset>
			<?php submit_button( 'Save Settings', 'btn btn-sm button-sti-red', 'iasemantify-submit-apikey', true ); ?>
        </form>
        <button type="button"
                title="Don't know your api-key? Login with your semantify account below or register if you don't have an account yet!"
                class="btn btn-xs button-sti-red" data-toggle="collapse" data-target="#login_div" id="login_id">
            <i class="material-icons">list</i>Don't know your api-keys?
        </button>
        <button title="reset to default api-key" id="iasemantify-reset-default-apikey"
                class="btn btn-xs button-sti-red"><i class="material-icons">cached</i>Reset Default
        </button>
        <div id="login_div" class="collapse" style="background-color: #e6e1e1;">
            <div style="padding: 10px 10px;">
                <br/>
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6">
                        <div id="iasemantify_loginSection"></div>
                    </div>
                </div>
            </div>
        </div>
        <br/>
        <br/>
        <br/>
        <h3>Reset your Instant Annotation Post metadata</h3>
        <p>Resetting your post metadata will delete all your IA boxes on all your posts. This should only be used in
            case of some error, and where the resetting for the individual post doesn't help</p>
        <button title="Reset your IA post metadata" id="iasemantify-reset-metadata" class="btn btn-xs button-sti-red"><i
                    class="material-icons">cached</i>Reset All Post Metadata
        </button>
        <hr style="border-color:black; margin-top:30px">
        <p>

        <div class="alert alert-warning" role="alert" style="opacity: 0.8">
            <h4>Auto-annotation-lookup</h4>
            </br>If this is enabled, the url of your post is taken, and the annotations on semantify.it which contain
            this url are automatically inserted. Please make sure, that you do not manually add annotations that contain
            the same url. If you do so, the automatic insertion will not work to prevent duplications!
            </br><label style="margin-top:50px">
                <input id="ia_ann_by_url_injection" type="checkbox"/>
                <span style="color:grey">Automatically insert annotations by url (EXPERIMENTAL)</span>
            </label>
        </div>
        </p>
        <script>
            iasemantify_addLogin();

            jQuery('#ia_postForm').submit(function (e) {
                var uid=jQuery('#iasemantify-websiteUID').val();
                var secret= jQuery('#iasemantify-websiteSecret').val();
                if((uid!=='DEFAULT' && uid!=='' ) || (secret!=='DEFAULT' && secret!=='')){
                    //Changed to non-default
                    //Check for annotations to import that are on default
                    var allPostIds=[];
                    allPostIds=  <?php echo json_encode( get_posts(array(
                        'post_type'       => 'any',
                        'fields'          => 'ids',
                        'posts_per_page'  => -1,
                        'post_status'    => 'any'
                    ))) ;?>;
                    var ia_all_promises=[];
                    var ia_all_annotations=[];
                    for(var i=0;i<allPostIds.length;i++){
                        var nonce = $('#ia-data').attr("data-nonce");

                        var request = $.ajax({
                            type: "post",
                            url: myAjax.ajaxurl,
                            data: {
                                action: "iasemantify_get_default_ann_by_id",
                                nonce: nonce,
                                postid: allPostIds[i]
                            },
                            success: function (res) {
                                var existingAnnotationsArray = JSON.parse(res).data.split(',');    //shift because string starts with ','
                                existingAnnotationsArray.shift();
                                var id=JSON.parse(res).id;
                                var postMeta = existingAnnotationsArray.map(function (value) {
                                    var splits = value.split(';');
                                    return {
                                        annotation_uid: splits[0],
                                        domainSpecification_hash: splits[1],
                                        website_uid: splits[2],
                                        website_secret: splits[3],
                                        post_id: id
                                    }
                                });
                                ia_all_annotations.push(postMeta);
                            },
                            error: function (err) {
                                console.log(err);
                                InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not update.")
                            }
                        });
                        ia_all_promises.push( request);

                    }
                    //DEFAULT WEBISTE
                    var ia_default_website_secret='ef0a64008d0490fc4764c2431ca4797b';
                    var ia_default_website_uid='Hkqtxgmkz';
                    var semantify_base_url='https://semantify.it/api/';

                    $.when.apply(null, ia_all_promises).done(function(){
                        var ann_stored_in_default=[];
                        ia_all_annotations.forEach(function(ann){
                            ann.forEach(function(a){
                                if(a.website_secret===ia_default_website_secret && a.website_uid===ia_default_website_uid){
                                    ann_stored_in_default.push(a);
                                }
                            });
                        });
                        var ia_confirm_migration=false;
                        if(ann_stored_in_default.length>0){
                            ia_confirm_migration = confirm('You have '+ ann_stored_in_default.length + ' annotation/s stored on the default website. Do you want to migrate them to your new website?');
                        }
                        if (ia_confirm_migration === true ) {
                            var nonce = $('#ia-data').attr("data-nonce");
                            $.ajax({
                                type: "post",
                                url: myAjax.ajaxurl,
                                data: {
                                    action: "iasemantify_migrate_annotations_get",
                                    nonce: nonce,
                                    annotations: ann_stored_in_default,
                                    new_web_uid:uid,
                                    new_web_secret:secret
                                },
                                success: function (res) {
                                    var ia_all_post_promises=[];
                                    var ia_all_post_annotations=[];
                                    JSON.parse(res).forEach(function(d){
                                        if(d!==null){
                                            (function(d) {
                                                var header = {
                                                    'website-secret': d.secret
                                                };
                                                    var data= [{
                                                    content: JSON.parse(d.data),
                                                    dsHash: d.hash
                                                }];
                                                var postrequest = $.ajax({
                                                    type: "post",
                                                    url: semantify_base_url + 'annotation/' + d.uid,
                                                    headers: header,
                                                    contentType: "application/json; charset=utf-8",
                                                    data: JSON.stringify(data),
                                                    dataType: 'json',
                                                    success: function (res) {
                                                        var temp={
                                                            old:d,
                                                            new:res[0]
                                                        };
                                                        ia_all_post_annotations.push(temp);
                                                    },
                                                    error: function (err) {
                                                        console.log(err);
                                                        InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not migrate.")
                                                    }
                                                });
                                                ia_all_post_promises.push(postrequest);
                                            })(d);
                                        }
                                    });
                                    $.when.apply(null, ia_all_post_promises).done(function(){
                                            $.ajax({
                                                type: "post",
                                                url: myAjax.ajaxurl,
                                                data: {
                                                    action: "iasemantify_migrate_annotations_cleanup",
                                                    nonce: nonce,
                                                    data: ia_all_post_annotations
                                                },
                                                success: function (res) {
                                                    console.log(res);
                                                    
                                                },
                                                error: function (err){
                                                    console.log(err);
                                                    InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not cleanup migration.");
                                                   
                                                },
                                            });

                                    });

                                },
                                error: function (err) {
                                    console.log(err);
                                    InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not migrate.");
                
                                }
                            });

                        }

                    });

                }
            });

            jQuery('#iasemantify-reset-default-apikey').click(function (e) {
                //e.preventDefault();
                jQuery('#iasemantify-websiteUID').val("DEFAULT");
                jQuery('#iasemantify-websiteSecret').val("DEFAULT");
                jQuery('#iasemantify-submit-apikey').click();
            })

            var IA_injection_is_checked = "<?php Print( $ia_is_checked ); ?>";
            if (IA_injection_is_checked === 'true') {
                $('#ia_ann_by_url_injection').prop("checked", true);
            } else {
                $('#ia_ann_by_url_injection').prop("checked", false);
            }
            $('#ia_ann_by_url_injection').change(function () {
                var nonce = $('#ia-data').attr("data-nonce");
                if ($('#ia_ann_by_url_injection').is(':checked')) {
                    $.ajax({
                        type: "post",
                        url: myAjax.ajaxurl,
                        data: {
                            action: "iasemantify_setting_url_injection",
                            nonce: nonce,
                            checked: 'true'
                        },
                        success: function (res) {
                            InstantAnnotation.util.send_snackbarMSG("Inserting annotations automatically!");
                        },
                        error: function (err) {
                            console.log(err);
                            InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not store changes.")
                        }
                    });

                } else {
                    $.ajax({
                        type: "post",
                        url: myAjax.ajaxurl,
                        data: {
                            action: "iasemantify_setting_url_injection",
                            nonce: nonce,
                            checked: 'false'
                        },
                        success: function (res) {
                            InstantAnnotation.util.send_snackbarMSG("Stopped automated insertion!");
                        },
                        error: function (err) {
                            console.log(err);
                            InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not store changes.")
                        }
                    });
                }
            });
            $('#iasemantify-reset-metadata').click(function(){
                var confirmation = confirm('Are you sure you want to reset Instant Annotation?');
                var nonce = $('#ia-data').attr("data-nonce");
                if (confirmation) {
                    $.ajax({
                        type: "post",
                        url: myAjax.ajaxurl,
                        data: {
                            action: "iasemantify_reset_all",
                            nonce: nonce,
                        },
                        success: function (res) {
                            InstantAnnotation.util.send_snackbarMSG("Metadata cleared");
                        },
                        error: function (err) {
                            console.log(err);
                            InstantAnnotation.util.send_snackbarMSG_fail("An error occurred. Could not reset the metadata changes.")
                        }
                    });
                }
            });
        </script>
    </div>
</div>