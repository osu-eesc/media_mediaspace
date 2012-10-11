<?php
/**
* @package Kaltura
* @author Shayne Huddleston
*/

/**
* KalturaClient and KalturaClientBase (implicitly) are both required for this class
*/
require_once "client/KalturaClient.php";

/**
* @todo Choose an accurate API_USER...talk to Raul...right now I randomly chose admin
* @todo add a delete media function and test it
* @todo get_users, and get_videos need handling to deal with paging (ie pages of results)
*/

/**
* @package Kaltura
* @author Shayne Huddleston
*
* This is primarly an abstraction layer for kaltura's client api v3. 
* We have added some convienence functions and some robustness with
* regards to many of the operations as this will likely be used by 
* a web based interface and users directly
*
* Kaltura API v3 documentation:
* http://www.kaltura.com/api_v3/testmeDoc/index.php?page=overview
*
* Kaltura Client Libraries
* http://www.kaltura.com/api_v3/testme/client-libs.php
*
* Some Examples (may be old)
* http://www.kaltura.org/kalorg/kaltura-api-clients-sample-code/php/
*
*/
class Kaltura {
  
  // Private OSU Kaltura credentials
  const PARTNER_ID = '391241';
  const ADMIN_SECRET = "342eb48cd46804c1f452dc4f341e9330";
  
  // The user id the session and commands will execute as
  const API_USER = "admin";
  
  // Client object
  private $client;
  
  /**
  * @author Shayne Huddleston
  *
  * Intialize the Kaltura class
  *
  * Set up our configuration and start the client as well as start up a session 
  *
  */
  function __construct() {
    
    // Set up our configuration and session for the client
    $conf = new KalturaConfiguration(self::PARTNER_ID);
    $conf->format = KalturaClientBase::KALTURA_SERVICE_FORMAT_PHP;
    $this->client = new KalturaClient($conf);
    $session = $this->client->session->start(self::ADMIN_SECRET, API_USER, KalturaSessionType::ADMIN, self::PARTNER_ID);
    
    // If something happened and we have no session bail out
    if (!isset($session)) {
    	die("Could not establish Kaltura session. Please verify that you are using valid Kaltura partner credentials.");
    }
    
    // Start a session for our client
    $this->client->setKs($session);
  }
  
  
  /**
  * Add User
  *
  * @author Shayne Huddleston
  * @param string $username a valid onid username
  * @return bool|false
  */
  function user_add($username){
    
    // Intialize a new Kaltura User Object
    $kuser = New KalturaUser;
    $kuser->id = "$username";
    $kuser->partnerId = self::PARTNER_ID;
    $kuser->screenName = "$username";
    $kuser->fullName = "$username";
    $kuser->firstName = "$username";
    $kuser->status = KalturaUserStatus::ACTIVE;
    
    // Create the User in Kaltura
    try{
      $result = $this->client->user->add($kuser);
    }
    catch (KalturaException $e){
      // Most likely the user already exists
      return false;
    }
    
    return true;
  }
  
  
  /**
  * Delete User
  *
  * @author Shayne Huddleston
  * @param string $username a valid username (onid)
  * @return bool|false
  */
  function user_delete($username){
    
    try{
      $result = $this->client->user->delete($username);
    }
    catch (KalturaException $e){
      // Tried to delete an invalid user id or a user 
      // that had already been deleted
      return false;
    }
    
    return true;
  }
    
    
  /**
  * Get all Users
  *
  * @return array 
  */
  function get_users(){
    
    // Only grab active users
    $filter = new KalturaUserFilter();
    $filter->status = KalturaUserStatus::ACTIVE;
    $filter->orderBy = "+id";
    
    // By default we retrieve 30 results...if we want more 
    // we need to ask for them
    $pager = new KalturaFilterPager();
    $pager->pageSize = 500;
    
    // Grab all our users out of Kaltura
    $result = $this->client->user->listAction($filter, $pager);
      
    return $result->objects;
  }  
  
  
  /**
  * Get User
  *
  * Get a valid user. If the user is not valid it will throu
  * an exception
  *
  * @author Shayne Huddleston
  * @param string $username a valid username
  * @return array|false 
  */
  function get_user($username){
    
    // Get the user from kaltura
    $result = $this->client->user->get($username);
    
    return $result;
    
  }
  
  
  /**
  * Get all Video
  *
  * @author Shayne Huddleston
  * @return array
  */
  function get_videos(){
    
    // Filter on Video media that is has a ready status
    $filter = new KalturaMediaEntryFilter();
    $filter->mediaTypeEqual = KalturaMediaType::VIDEO;
    $filter->status = KalturaEntryStatus::READY;
      
    // By default we retrieve 30 results...if we want more
    // we need to ask for them
    $pager = new KalturaFilterPager();
    $pager->pageSize = 500;
    $videos = array();
    $i = 1;
    do {
      $pager->pageIndex = $i++;
      $result = $this->client->media->listAction($filter,$pager);
      $videos = array_merge($videos, $result->objects);
    } while (count($result->objects) > 0);
    
    return $videos;
    
  }
  
  
  /**
  * Get Single Video
  *
  * Get a single video by its id. If the id is not valid
  * it will throw an exception
  *
  * @author Shayne Huddleston
  * @param string $video_id a valid video id
  * @return array
  */
  function get_video($video_id){
    
    // Filter on Video media that is has a ready status
    $filter = new KalturaMediaEntryFilter();
    $filter->mediaTypeEqual = KalturaMediaType::VIDEO;
    $filter->idEqual = $video_id;
    
    $result = $this->client->media->listAction($filter);
    
    return $result->objects;
    
  }
  
  
  /**
  * Get Users's Videos
  *
  * Get all videos that belong to a given user
  *
  * @author Shayne Huddleston
  * @param string $username a valid username
  * @return array
  */
  function get_videos_by_user($username){
    
    // Filter on Video media that is has a ready status
    $filter = new KalturaMediaEntryFilter();
    $filter->mediaTypeEqual = KalturaMediaType::VIDEO;
    $filter->status = KalturaEntryStatus::READY;
    $filter->userIdEqual = $username;
      
    // By default we retrieve 30 results...if we want more
    // we need to ask for them
    $pager = new KalturaFilterPager();
    $pager->pageSize = 100;
    
    $result = $this->client->media->listAction($filter,$pager);
    
    return $result->objects;
    
  }
  
  function get_videos_after_date($timestamp) {
    
    // Filter on Video media that is has a ready status
    $filter = new KalturaMediaEntryFilter();
    $filter->createdAtGreaterThanOrEqual = $timestamp;
      
    // By default we retrieve 30 results...if we want more
    // we need to ask for them
    $pager = new KalturaFilterPager();
    $pager->pageSize = 500;
    $videos = array();
    $i = 1;
    do {
      $pager->pageIndex = $i++;
      $result = $this->client->media->listAction($filter,$pager);
      $videos = array_merge($videos, $result->objects);
    } while (count($result->objects) > 0);
    
    return $videos;
  }
  
  
  /**
  * Get Media Owner
  *
  * Convienence function for getting the owner of a piece of media
  *
  * @author Shayne Huddleston
  * @param string $video_id a valid mediaId
  * @return string|false
  */
  function get_video_owner_id($video_id){
    
    // Filter on Video media that is has a ready status
    $filter = new KalturaMediaEntryFilter();
    $filter->mediaTypeEqual = KalturaMediaType::VIDEO;
    $filter->idEqual = $video_id;
      
    $result = $this->client->media->listAction($filter);
    
    // We should stop if we could not find any media with
    // the provided id and throw and exception
    if (empty($result->objects))
      throw new Exception('Invalid Media ID');
      
    return $result->objects[0]->userId;;
    
  }
  
  
  /**
  * Change Media Ownership
  *
  * Function for updating the ownership of media. It will set or 
  * overwrite the current owner of the media with the supplied user id
  *
  * @author Shayne Huddleston
  * @param string $video_id a valid mediaId
  * @param string $new_owner a valid userId
  * @return bool|false 
  */
  function update_video_owner($video_id, $new_owner){
    
    // If the new_owner does not exist or is otherwise invalid stop
    try{    
      $this->get_user($new_owner);
    }
    catch(Exception $e){
      return false;
    }
    
    $video = new KalturaBaseEntry();
    $video->userId = $new_owner;
    
    try{
      $result = $this->client->baseEntry->update($video_id,$video);
    }
    catch (KalturaException $e){
      return false;
    }
    
    return true;
    
  }


  /**
  * Change the date of when a video was last updated
  *
  * Function for updating when a video was last updated. It will overwrite
  * whatever timestamp is currently set with the supplied timestamp
  * if it is newer than what is in Kaltura.
  *
  * @author Richard Middaugh
  * @param string $video_id a valid mediaId
  * @param integer $timestamp a new time in UNIX timestamp form
  * @return bool|false 
  */
  function update_video_updated_date($video_id, $timestamp){
    $original_video = $this->get_video($video_id);
    $original_video_date = $original_video[0]->updatedAt;

    if ($original_video_date < $timestamp) {
      $video = new KalturaBaseEntry();
      $video->updatedAt = $timestamp;
      try{
        $result = $this->client->baseEntry->update($video_id,$video);
      }
      catch (KalturaException $e){
        return false;
      }
      return true;
    }
    return false;
  }


  /**
   * Give a video a "Display Name"
   *
   * Fills in the display name for a video. This is done by setting a tag
   * on the video of "displayname_{onid_user_name}".
   * This is found by looking at the video's creator, which is always set.
   * It will not overwrite values that are already set.
   *
   * @author Richard Middaugh
   * @param string $video_id a valid video id
   * @return bool|false
   */
  function fill_in_provider_and_display_name_for_video($video_id){
    $original_video = array_pop($this->get_video($video_id));
    $display_tag = "displayname_$original_video->userId";
    if (stripos($original_video->tags, "displayname_") !== false) return true;
    $video = new KalturaBaseEntry();
    $video->tags = "$original_video->tags, $display_tag";
    try{
      $result = $this->client->baseEntry->update($video_id,$video);
    }
    catch (KalturaException $e){
      return false;
    }
    return true;
  }


  /**
  * Delete Video
  *
  * Deletes a video. If the video id is not valid
  * it will throw an exception
  *
  * @author Shayne Huddleston
  * @param string $video_id a valid video id
  */
  function delete_video($video_id){
    
    $this->client->media->delete($video_id);
    
  }
  
  
  /**
  * Add Video
  *
  * This function will upload a local media file up to Kaltura.
  * If the owner does not exist it will create the user before
  * assigning ownership to them
  *
  * @author Shayne Huddleston
  * @param string $path the path to the video
  * @param string $name the name of the video
  * @param string $description the description of the video
  * @param string $tags any tags that apply to the video
  * @param string $categories any categories the video belongs to
  * @param string $username username the video should belong to
  * @return string|false returns the kaltura videoId or an error
  *
  */
  function add_video($path, $name, $description, $tags, $categories, $username){
    
    // Check to see if the user already exists..if they
    // do not then add them  
    try{
      $user = $this->get_user($username);
    }
    // Check explicity for a invalid user id error 
    // here to be 100% accurate...right now we ass-ume :)
    catch (KalturaException $e){
      // Create user
      $user = $this->user_add($username);
    }
    
    try{
      // Create a new Kaltura Media Entry with the appropriate metadata
      $video = new KalturaMediaEntry();
      $video->name = $name;
      $video->description = $description;
      $video->tags = $tags;
      $video->categories = $categories;
      $video->partnerID = self::PARTNER_ID;
      $video->mediaType = KalturaMediaType::VIDEO;
      $video->userId = $username;
      
      // Upload the new video
      $result = $this->client->media->addFromUrl($video, $path);
    }
    catch (KalturaException $e){
      $this->log(sprintf("Video Upload Error: $s $s", $name, $e->getMessage()));
      throw new Exception('Media Upload Failed');
    }
    
    // If it worked then return the video_id else...throw an error
    if (empty($result)){ // This really needs to be more sophisticated...maybe response object type?
      $this->log(sprintf("Video Upload Error: $s $s", $name, "Empty result set returned"));
      throw new Exception('Media Upload Failed');
    }
      
    return $result->id;
  }
  
  
  /**
  * Add test video
  *
  * This function will add a local video for our testing purposes
  *
  * @author Shayne Huddleston
  */
  function add_test_video(){
    
    // Add test video with local media, and sample Name, Description, and Owner
    return $this->add_video("http://video.cws.oregonstate.edu/xwksn-std.mp4", 
                            "Final performance part 2", 
                            "Second half of the final concert", 
                            "SC,choir,conducting",
                            "Other",
                            "huddlesh");
    
  }

  
  /**
  * A simple print function with formatting
  *
  * This function is really just a useful debuging tool...it has not 
  * place anywhere else as it dumps its output directly to the screen.
  *
  * @author Shayne Huddleston
  * @param mixed $arg a php object you want to print
  */
  function pretty_print($arg){
    
    // If its an array or object (same diff weaksauce php) then use 
    // print_r otherwise cast the var to a string and print normally
    print "<pre>";
    if (is_array($arg) || is_object($arg))
      print_r($arg);
    else{
      $arg = (String) $arg;
      print $arg;
    }
    print "</pre>";
    
  }
  
  
  /**
  * Write message to log
  *
  * Log any message we want to file. For example if a video fails to
  * upload we want to record the pertinent information. The log will
  * automatically be created if it does not exist yet.
  *
  * @author Shayne Huddleston
  * @param string $message message to log
  */
  function log($message){
    
    try{
    // Create or open our log file in append mode and get a handle on it
    $log = fopen("log.txt", "a");
    
    // Write our message 
    fwrite($log, $message . "\n");
    
    // Close handle to log file
    fclose($log);
    }
    catch(Exception $e){
      // Throw a helpful error if we fail here
      throw new Exception('Problem writing to log. Does the webserver 
                           have write permissions to this directory?');
    }
    
  }

  /**
  * Deletes video flavors and all video assets that use them
  *
  * First deletes all video assets that are using a given flavor param id,
  * then deletes the flavor params themselves so they can no longer be used.
  *
  * @author Richard Middaugh
  * @param array $old_flavor_ids list of flavor param ids to delete
  * @param array $videos list of specific videos to run on instead of all available videos
  */
  function remove_unneeded_flavors_by_ids($old_flavor_ids, $videos = null){
    $kfas = new KalturaFlavorAssetService($this->client);

    if (!$videos){
      $videos = $this->get_videos();
    }
    foreach ($videos as $video){
      // Go through each flavor asset the video has
      foreach ($kfas->getByEntryId($video->id) as $flavor_asset){
        // Delete flavor asset if it's for a flavor we don't want
        if (in_array($flavor_asset->flavorParamsId, $old_flavor_ids)){
          $kfas->delete($flavor_asset->id);
        }
      }
    }
  }

  /**
   * Creates a new flavor to be used based off a currently existing flavor
   *
   * Grabs a currently existing flavor and changes it using passed in variables, then
   * adds the changed flavor as a new flavor to Kaltura. The changes to be made
   * should be passed as an array with variables of the flavor.
   *
   * @author Richard Middaugh
   * @param integer $current_flavor_id ID of flavor to copy
   * @param array $cur_flavor_changes list of keys and values to change before saving
   */
  function add_flavor_from_current_flavor($current_flavor_id, $cur_flavor_changes){
    $kfps = new KalturaFlavorParamsService($this->client);
    $new_flavor = $kfps->get($current_flavor_id);
    $new_flavor->id = null;
    $new_flavor->createdAt = null;
    foreach ($cur_flavor_changes as $key => $value){
      if ($new_flavor->$key){
        $new_flavor->$key = $value;
      }
    }
    $flavor = $kfps->add($new_flavor);
    return $flavor;
  }

  /**
   * Adds a new flavor to a specific piece of media
   *
   * Tries to add a video flavor to a video. It will not add
   * the flavor if it already has it.
   *
   * @author Richard Middaugh
   * @param integer $flavor_id ID of flavor to give to each video
   * @param KalturaMediaEntry $video object that describes video to add flavor to
   */
  function flavor_video($flavor_id, $video){
    if (!in_array(explode(",", $video->flavorParamsIds), $flavor_id)){
       $kfas = new KalturaFlavorAssetService($this->client);
       $kfas->convert($video->id, $flavor_id);
    }
  }

  /**
   * Adds a new flavor to media
   *
   * Goes through each video and tries to add a video flavor to it.
   *
   * @author Richard Middaugh
   * @param integer $flavor_id ID of flavor to give to each video
   * @param array $videos list of videos to specifically run it on instead of all of them
   */
  function flavor_videos($flavor_id, $videos = null){
    if (!$videos){
      $videos = $this->get_videos();
    }
    foreach ($videos as $video){
      $this->flavor_video($flavor_id, $video);
    }
  }
  
  /**
   * Reports on each video in Kaltura that has captions
   *
   * For each piece of KalturaMetadata that is part of our custom captioning field,
   * get the associated video and print out the ID and name of it, as well as the
   * location of the captioning file (Located in the XML data for the KalturaMetadata
   *
   * @author Richard Middaugh
   * @return string $output html table output of data
   */
  function report_captioned_videos(){
    $kmps = new KalturaMetadataProfileService($this->client);
    $kms = new KalturaMetadataService($this->client);
    $filter = new KalturaMetadataFilter($this->client);

    $captioning_id = $kmps->listAction()->objects[0]->id;
    $filter->metadataProfileIdEqual = $captioning_id;
    $metadata_actions = $kms->listAction($filter)->objects;

    $output = "<table>";
    foreach ($metadata_actions as $caption_data) {
      if (!empty($caption_data->xml)) {
        $video = $this->get_video($caption_data->objectId);
        $video_title = '';
        if (isset($video[0]->name)) $video_title = $video[0]->name;
        $output .= "<tr><td>" . $caption_data->objectId . "</td><td>" . $video_title . "</td><td>" . $caption_data->xml . "</td></tr>";
      }
    }
    $output .= "</table>";
    return $output;
  }  
  
}

?>
