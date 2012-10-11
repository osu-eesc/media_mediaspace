<?php

/**
 * @file
 *  This is an kaltura provider include file for Embedded Media Video.
 *  Author:  Bobby Kramer, 2011-04-13, based on emfield example
 *
 *  When using this, first make the following global replacements:
 *    * Replace kaltura with the name of your provider in all caps.
 *    * Replace kaltura with the name of your provider in all lower case.
 *    * Replace kaltura with the name (to be translated) of your provider in
 *        uppercase.
 *
 *  You then need to go through each function and modify according to the
 *  requirements of your provider's API.
 */

/**
 *  This is the main URL for your provider.
 */
define('EMVIDEO_MEDIASPACE_MAIN_URL', 'http://media.oregonstate.edu/');

/**
 *  This defines the version of the content data array that we serialize
 *  in emvideo_kaltura_data(). If we change the expected keys of that array,
 *  we must increment this value, which will allow older content to be updated
 *  to the new version automatically.
 */
define('EMVIDEO_MEDIASPACE_DATA_VERSION', 1);

/**
 * hook emvideo_PROVIDER_info
 * This returns information relevant to a specific 3rd party video provider.
 *
 * @return
 *   A keyed array of strings requested by various admin and other forms.
 *    'provider' => The machine name of the provider. This must be the same as
 *      the base name of this filename, before the .inc extension.
 *    'name' => The translated name of the provider.
 *    'url' => The url to the main page for the provider.
 *    'settings_description' => A description of the provider that will be
 *      posted in the admin settings form.
 *    'supported_features' => An array of rows describing the state of certain
 *      supported features by the provider. These will be rendered in a table,
 *      with the columns being 'Feature', 'Supported', 'Notes'. In general,
 *      the 'Feature' column will give the name of the feature, 'Supported'
 *      will be Yes or No, and 'Notes' will give an optional description or
 *      caveats to the feature.
 */
function emvideo_mediaspace_info() {
  $features = array(
    array(t('Full screen mode'), t('Yes'), t('You may customize the player to enable or disable full screen playback. Full screen mode is enabled by default.')),
  );
  return array(
    'module'   => 'media_mediaspace',
    'provides' => array('emvideo'),
    'provider' => 'mediaspace',
    'name'     => t('MediaSpace'),
    'url'      => EMVIDEO_MEDIASPACE_MAIN_URL,
    'settings_description' => t('These settings specifically affect videos displayed from !kaltura.', array('!kaltura' => l(t('Kaltura.com'), EMVIDEO_MEDIASPACE_MAIN_URL))),
    'supported_features'   => $features,
  );
}

/**
 *  hook emvideo_PROVIDER_settings
 *  This should return a subform to be added to the emvideo_settings() admin
 *  settings page.
 *
 *  Note that a form field set will already be provided at $form['kaltura'],
 *  so if you want specific provider settings within that field set, you should
 *  add the elements to that form array element.
 */
function emvideo_mediaspace_settings() {
  // We'll add a field set of player options here. You may add other options
  // to this element, or remove the field set entirely if there are no
  // user-configurable options allowed by the kaltura provider.
  $form['mediaspace']['player_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Embedded video player options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  // This is an option to set the video to full screen. You should remove this
  // option if it is not provided by the kaltura provider.
  $form['mediaspace']['player_options']['emvideo_mediaspace_full_screen'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow fullscreen'),
    '#default_value' => variable_get('emvideo_mediaspace_full_screen', 1),
    '#description' => t('Allow users to view video using the entire computer screen.'),
  );

  return $form;
}

/**
 *  hook emvideo_PROVIDER_extract
 *
 *  This is called to extract the video code from a pasted URL or embed code.
 *
 *  We'll be passed a URL or the embed code from a video when an editor pastes
 *  that in the field's textfield. We'll need to either pass back an array of
 *  regex expressions to match, or do the matching ourselves and return the
 *  resulting video code.
 *
 *  @param $parse
 *    An optional string with the pasted URL or embed code.
 *  @return
 *    Either an array of regex expressions to be tested, or a string with the
 *    video code to be used. If the hook tests the code itself, it should
 *    return either the string of the video code (if matched), or an empty
 *    array. Otherwise, the calling function will handle testing the embed code
 *    against each regex string in the returned array.
 */
function emvideo_mediaspace_extract($parse) {
	$patterns = array(
		'@media\.oregonstate\.edu/#entry_id=([^"\?]+)@i',
		'@media\.oregonstate\.edu/index\.php/show/\?id=([^"\?]+)@i',
	);
	foreach ($patterns as $p) {
		$test = preg_match($p, $parse, $matches);
		if ($test) {
			$result = $matches[1];
			break;
		}
	}
	if ($result) return 'wid/_391241/uiconf_id/4477922/entry_id/'.$result;
	else return array('@kaltura\.com/index.php/kwidget/([^"\?]+)@i',);
}

/**
 *  Implement hook emvideo_PROVIDER_data_version().
 */
function emvideo_mediaspace_data_version() {
  return EMVIDEO_MEDIASPACE_DATA_VERSION;
}


/**
 * hook emfield_PROVIDER_data
 *
 * provides an array to be serialised and made available with $item elsewhere
 *
 *  This data can be used to store any extraneous information available
 *  specifically to the example provider.
 *
 * Calling our own service API to get all of the info on this video
 */
function emvideo_mediaspace_data($field, $item) {
  $data  = array();

  // Extract thie video ID from the embed string
  $value = explode('/', $item['value']);
  $id    = $value[5];

  // Call our Kaltura webservice
  // $info = file_get_contents('http://webdev.cws.oregonstate.edu/liebermp/kaltura/?id=' . $id);
  // $media = json_decode($info);

  // or instantiate the Kaltura service here instead
  require_once( drupal_get_path('module', 'media_mediaspace') . '/kaltura/kaltura.php');

  // Create a new Kaltura object
  $k = new Kaltura();

  // Get video information
  $media = $k->get_video($id);

  // Extract data to our own array
  $data['thumbnail']   = $media[0]->thumbnailUrl;
  $data['height']      = $media[0]->height;
  $data['width']       = $media[0]->width;
  $data['duration']    = $media[0]->duration;
  $data['name']        = $media[0]->name;
  $data['description'] = $media[0]->description;
  $data['tags']        = $media[0]->tags;
  $data['url']         = $media[0]->downloadUrl;
  $data['data']        = $media[0]->data;

  return $data;
}

/**
 * hook emvideo_PROVIDER_duration($item)
 * Returns the duration of the video in seconds.
 *  @param $item
 *    The video item itself, which needs the $data array.
 *  @return
 *    The duration of the video in seconds.
 */
function emvideo_mediaspace_duration($item) {
  $item['data'] = emvideo_mediaspace_data(NULL, $item);
  return isset($item['data']['duration']) ? $item['data']['duration'] : 0;
}


/**
 * hook emvideo_PROVIDER_thumbnail
 * Returns the external url for a thumbnail of a specific video.
 *  @param $field
 *    The field of the requesting node.
 *  @param $item
 *    The actual content of the field from the requesting node.
 *  @return
 *    A URL pointing to the thumbnail.
 */
function emvideo_mediaspace_thumbnail($field, $item, $formatter, $node, $width, $height) {

  if (! isset($item['data']['thumbnail'])) {
    $item['data'] = emvideo_mediaspace_data(NULL, $item);
  }
  return $item['data']['thumbnail'];
}


/**
 * The embedded flash displaying the kaltura video.
 */
function theme_emvideo_mediaspace_flash($item, $width, $height, $autoplay) {

  $autoplay = $autoplay ? 'true' : 'false';
	$fullscreen = variable_get('emvideo_mediaspace_full_screen', 1) ? 'true' : 'false';

	$embed  =  '<object id="kaltura_player"	 name="kaltura_player" type="application/x-shockwave-flash"';
 	$embed .=  'allowFullScreen="true"   allowNetworking="all"  allowScriptAccess="always"';
 	$embed .=	 'height="' . $height . '"';
 	$embed .=	 'width="'  . $width  . '"';
  $embed .=	 'xmlns:dc="http://purl.org/dc/terms/" xmlns:media="http://search.yahoo.com/searchmonkey/media/"';
 	$embed .=	 'rel="media:video"';
 	$embed .=	 'resource="http://www.kaltura.com/index.php/kwidget/' . $item['value'] . '"';
 	$embed .=	 'data="http://www.kaltura.com/index.php/kwidget/' . $item['value'] . '" >';
  $embed .= '<param name="wmode" value="opaque" />';
	$embed .= '<param name="allowFullScreen" value="'. $fullscreen .'" />';
	$embed .= '<param name="allowNetworking" value="all" />';
	$embed .= '<param name="allowScriptAccess" value="always" />';
 	// $embed .= '<param name="bgcolor" value="#000000" />';
 	$embed .= '<param name="flashVars" value="&" />';
 	$embed .= '<param name="FlashVars" value="&autoPlay='. $autoplay .'" />';
 	$embed .= '<param name="movie" value="http://www.kaltura.com/index.php/kwidget/' . $item['value'] .'">';
 	$embed .= '</object>';

  return $embed;
}

/**
 * hook emvideo_PROVIDER_embedded_link($video_code)
 * returns a link to view the video at the provider's site
 *  @param $video_code
 *    the string containing the video to watch
 *  @return
 *    a string containing the URL to view the video at the original provider's site
 */
function emvideo_mediaspace_embedded_link($video_code) {

  $path = explode('/', $video_code);
  $id   = $path[5];
  $data = emvideo_mediaspace_data(NULL, array('value' => $video_code));

  // Fix spaces and uppercase in $data['name']
  $dept = strtolower(preg_replace('/ /', '_', $data['name']));

  // Return a URL to view on media.o.e
  return 'http://media.oregonstate.edu/index.php/show/'. $dept . '?id=' . $id;
}


/**
 *  hook emvideo_PROVIDER_video
 *  This actually displays the full/normal-sized video we want, usually on the
 *  default page view.
 *  @param $embed
 *    The video code for the video to embed.
 *  @param $width
 *    The width to display the video.
 *  @param $height
 *    The height to display the video.
 *  @param $field
 *    The field info from the requesting node.
 *  @param $item
 *    The actual content from the field.
 *  @return
 *    The html of the embedded video.
 */
function emvideo_mediaspace_video($embed, $width, $height, $field, $item, $node, $autoplay) {
  $output = theme('emvideo_mediaspace_flash', $item, $width, $height, $autoplay);
  return $output;
}

/**
 *  hook emvideo_PROVIDER_video
 *
 *  This actually displays the preview-sized video we want, commonly for the
 *  teaser.
 *  @param $embed
 *    The video code for the video to embed.
 *  @param $width
 *    The width to display the video.
 *  @param $height
 *    The height to display the video.
 *  @param $field
 *    The field info from the requesting node.
 *  @param $item
 *    The actual content from the field.
 *  @return
 *    The html of the embedded video.
 */
function emvideo_mediaspace_preview($embed, $width, $height, $field, $item, &$node, $autoplay) {
  $output = theme('emvideo_mediaspace_flash', $item, $width, $height, $autoplay);
  return $output;
}

/**
 *  Implementation of hook_emfield_subtheme.
 *  This returns any theme functions defined by this provider.
 */
function emvideo_mediaspace_emfield_subtheme() {
  $themes = array(
    'emvideo_mediaspace_flash'  => array(
        'arguments' => array('item' => NULL, 'width' => NULL, 'height' => NULL, 'autoplay' => NULL),
        'file' => 'mediaspace.inc',
        'path' => drupal_get_path('module', 'media_mediaspace') . '/providers',
    )
  );
  return $themes;
}
