<?php
$IA_foundURL='false';

function iasemantify_printJson($json, $current_url, $id = "")
{

    if ($id == "") {
        $out = '<!-- Created by the instant annotator of semantify.it v' . IASEMANTIFY_PLUGIN_NAME_VERSION . ' (by url autodetection) -->';
    } else {
        $out = '<!-- Created by the instant annotator of semantify.it v' . IASEMANTIFY_PLUGIN_NAME_VERSION . ' (' . $id . ') -->';
    }

	$out.= '<script type="application/ld+json">';
	$out.= $json;
	$out.= '</script>';
	echo $out;
	$json=json_encode($json);
	global $IA_foundURL;
	if (strpos($json, $current_url) !== false) {
	    $IA_foundURL='true';
	}
}

$postId = get_the_ID();
if ( is_front_page() )
	$postId = get_option( 'page_on_front' );
if ( is_home() )
	$postId = get_option( 'page_for_posts' );

$fullAnnIdsStr = get_post_meta($postId, $this->plugin_name . "_ann_id", true);
$fullAnnIdsArr = explode(",", $fullAnnIdsStr);
array_shift($fullAnnIdsArr);       //remove first element
$current_url=get_permalink();
$current_url = str_replace('/', '\/', $current_url);
global $IA_foundURL;
$IA_foundURL = 'false';

foreach ($fullAnnIdsArr as $value){
	$annId = explode(";", $value)[0];
	try {
        $response = wp_remote_get("https://smtfy.it/" . $annId);
		if (!is_wp_error( $response ) && wp_remote_retrieve_response_code($response) === 200) {
			$body = wp_remote_retrieve_body($response);
            iasemantify_printJson($body, $current_url, $annId);
        } else {
            echo '<!-- There was a problem getting an annotation v' . IASEMANTIFY_PLUGIN_NAME_VERSION . ' (' . $annId . ') -->';
        }
	} catch (Exception $e) {
	}
}


if(get_option('iasemantify_setting_url_injection')=='true'){
    $current_url=get_permalink();
    $current_encoded_url= str_replace('/', '%2F', $current_url);
    $path='https://semantify.it/api/annotation/url/';
    $resultPath = $path . $current_encoded_url;
    global $IA_foundURL;
	try {
		$response = wp_remote_get( $resultPath);
		if ($IA_foundURL == 'false' && !is_wp_error( $response ) && wp_remote_retrieve_response_code($response) === 200) {
			$body = wp_remote_retrieve_body($response);
			iasemantify_printJson($body,  "$$$$$$$777");
        } else {
            echo '<!-- There was a problem getting an annotation by url v' . IASEMANTIFY_PLUGIN_NAME_VERSION . ' (' . $resultPath . ')  -->';
        }
	} catch (Exception $e) {

    }
}
