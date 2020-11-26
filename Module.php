<?php

namespace soareseneves\youtubeapi;

use Yii;
use yii\base\InvalidParamException;
use Carbon\Carbon;
use yii\web\ServerErrorHttpException;

class Module extends \yii\base\Module
{

    public $app_name = '';
    public $client_id = '';
    public $client_secret = '';
    public $api_key = '';
    public $redirect_url = '';
    public $yt_language = 'pt';

    protected $client;
    protected $youtube;
    protected $googleLiveBroadcastSnippet;
    protected $googleLiveBroadcastStatus;
    protected $googleYoutubeLiveBroadcast;
    protected $googleYoutubeLiveStreamSnippet;
    protected $googleYoutubeCdnSettings;
    protected $googleYoutubeLiveStream;
    protected $googleYoutubeVideoRecordingDetails;

    public function init(){
        parent::init();
        
        $this->client = new \Google_Client;
        $this->client->setClientId($this->client_id);
        $this->client->setClientSecret($this->client_secret);
        $this->client->setDeveloperKey($this->api_key);
        $this->client->setRedirectUri($this->redirect_url);
        $this->client->setScopes([
                                     'https://www.googleapis.com/auth/youtube',
                                 ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        $this->googleLiveBroadcastSnippet = new \Google_Service_YouTube_LiveBroadcastSnippet;
        $this->googleLiveBroadcastStatus = new \Google_Service_YouTube_LiveBroadcastStatus;
        $this->googleYoutubeLiveBroadcast = new \Google_Service_YouTube_LiveBroadcast;
        $this->googleYoutubeLiveStreamSnippet = new \Google_Service_YouTube_LiveStreamSnippet;
        $this->googleYoutubeCdnSettings = new \Google_Service_YouTube_CdnSettings;
        $this->googleYoutubeLiveStream = new \Google_Service_YouTube_LiveStream;
        $this->googleYoutubeVideoRecordingDetails = new \Google_Service_YouTube_VideoRecordingDetails;
    }

     /**
     * [setAccessToken -setting the access token to the client]
     * @param [type] $google_token [googel auth token]
     */
    public function setAccessToken($google_token = null)
    {
        try {
            
            if (!is_null($google_token))
                $this->client->setAccessToken($google_token);

            if (!is_null($google_token) && $this->client->isAccessTokenExpired()) {
                $refreshed_token = $this->client->getRefreshToken();
                $this->client->fetchAccessTokenWithRefreshToken($refreshed_token);
                $newToken = $this->client->getAccessToken();
                $newToken = json_encode($newToken);
            }

            return !$this->client->isAccessTokenExpired();

        } catch ( \Google_Service_Exception $e ) {
            
            throw new ServerErrorHttpException($e->getMessage(), 1);

        } catch ( \Google_Exception $e ) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        
        } catch(\Exception $e) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        }
    }

    /**
     * [broadcast creating the event on youtube]
     * @param  [type] $token [auth token for youtube channel]
     * @param  [type] $data  [array of the event details]
     * @return [type]        [response array of broadcast ]
     */
    public function broadcast($token, $data = null)
    {
        try{
            $response = [];
            if( count($data) < 1 || empty($data) || !isset($data['title']) || !isset($data['description']) ) {
                Yii::info('mandatory fields missing ind data');
                return false;
            }

            /** 
             * [setAccessToken [setting accent token to client]]
             */                 
            $setAccessToken = self::setAccessToken($token);
            if(!$setAccessToken)
                return false;   

            /** 
             * [$service [instance of Google_Service_YouTube ]]
             * @var [type]
             */         
            $youtube = new \Google_Service_YouTube($this->client);

            $title = $data["title"];
            $description = $data["description"];
            $thumbnail_path = isset($data["thumbnail_path"]) ? $data["thumbnail_path"] : null;
            $startdt = Carbon::createFromFormat('Y-m-d H:i:s', $data["event_start_date_time"], $data["time_zone"]);
            $startdt = ($startdt < Carbon::now($data["time_zone"])) ? Carbon::now($data["time_zone"]) : $startdt;
            $startdtIso = $startdt->toIso8601String();
            
            if ( count($data["tag_array"]) > 0){
                $tags = substr(str_replace(", ,", ",", implode(',', $data["tag_array"])),0,498);
                $tags = (substr($tags, -1)==',') ? substr($tags,0,-1) : $tags;
                $data["tag_array"] = explode(',', $tags);
            } else {
                $data["tag_array"] = [];
            }

            $privacy_status = isset($data['privacy_status']) ? $data['privacy_status'] : "public";
            $language = isset($data["language_name"]) ? $data["language_name"]: 'English';

            /** 
             * Create an object for the liveBroadcast resource [specify snippet's title, scheduled start time, and scheduled end time]
             */
            $this->googleLiveBroadcastSnippet->setTitle($title);
            $this->googleLiveBroadcastSnippet->setDescription($description);
            $this->googleLiveBroadcastSnippet->setScheduledStartTime($startdtIso);

            /**
             * object for the liveBroadcast resource's status ["private, public or unlisted"]
             */
            $this->googleLiveBroadcastStatus->setPrivacyStatus($privacy_status);

            /** 
             * API Request [inserts the liveBroadcast resource]
             */
            $this->googleYoutubeLiveBroadcast->setSnippet($this->googleLiveBroadcastSnippet);
            $this->googleYoutubeLiveBroadcast->setStatus($this->googleLiveBroadcastStatus);
            $this->googleYoutubeLiveBroadcast->setKind('youtube#liveBroadcast');

            /**
             * Execute Insert LiveBroadcast Resource Api [return an object that contains information about the new broadcast]
             */
            $broadcastsResponse = $youtube->liveBroadcasts->insert('snippet,status', $this->googleYoutubeLiveBroadcast, array());
            $response['broadcast_response'] = $broadcastsResponse;

            $youtube_event_id = $broadcastsResponse['id'];

            /**
             * set thumbnail to the event
             */
            if(!is_null($thumbnail_path))
                $thumb = self::uploadThumbnail($thumbnail_path, $youtube_event_id);

            /**
             * Call the API's videos.list method to retrieve the video resource.
             */
            $listResponse = $youtube->videos->listVideos("snippet", array('id' => $youtube_event_id));
            $video = $listResponse[0]; 

            /**
             * update the tags and language via video resource
             */
            $videoSnippet = $video['snippet'];
            $videoSnippet['tags'] = $data["tag_array"];
            if(!is_null($language)){
                $temp = isset($this->yt_language[$language]) ? $this->yt_language[$language] : "en"; 
                $videoSnippet['defaultAudioLanguage'] = $temp; 
                $videoSnippet['defaultLanguage'] = $temp;  
            }

            $video['snippet'] = $videoSnippet;

            /** 
             * Update video resource [videos.update() method.]
             */
            $updateResponse = $youtube->videos->update("snippet", $video);
            $response['video_response'] = $updateResponse;

            /**
             * object of livestream resource [snippet][title]
             */
            $this->googleYoutubeLiveStreamSnippet->setTitle($title);

            /**
             * object for content distribution  [stream's format,ingestion type.]
             */
            $this->googleYoutubeCdnSettings->setResolution("variable");
            $this->googleYoutubeCdnSettings->setFrameRate("variable");
            $this->googleYoutubeCdnSettings->setIngestionType('rtmp');

            /** 
             * API request [inserts liveStream resource.]
            /** 
             * API request [inserts liveStream resource.]
             */
            $this->googleYoutubeLiveStream->setSnippet($this->googleYoutubeLiveStreamSnippet);
            $this->googleYoutubeLiveStream->setCdn($this->googleYoutubeCdnSettings);
            $this->googleYoutubeLiveStream->setKind('youtube#liveStream');

            /*
            /**
             * execute the insert request [return an object that contains information about new stream]
             */
            $streamsResponse = $youtube->liveStreams->insert('snippet,cdn', $this->googleYoutubeLiveStream, array());
            $response['stream_response'] = $streamsResponse;

            /**
             * Bind the broadcast to the live stream
            /**
             * Bind the broadcast to the live stream
             */
            $bindBroadcastResponse = $youtube->liveBroadcasts->bind(
                $broadcastsResponse['id'],'id,contentDetails',
                array(
                    'streamId' => $streamsResponse['id'],
                ));

            $response['bind_broadcast_response'] = $bindBroadcastResponse;

            Yii::info('------------ BIND BROADCAST RESPONSE -------------');
            Yii::info(json_encode($bindBroadcastResponse));

            return $response;

        } catch ( \Google_Service_Exception $e ) {

            throw new ServerErrorHttpException($e->getMessage(), 1);

        } catch ( \Google_Exception $e ) {


            throw new ServerErrorHttpException($e->getMessage(), 1);
        
        } catch(\Exception $e) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        }

    }

    /**
     * [uploadThumbnail upload thumbnail for the event]
     * @param  string $url     [path to image]
     * @param  [type] $videoId [eventId]
     * @return [type]          [thumbnail url]
     */
    public function uploadThumbnail($url = '', $videoId)
    {
        if($this->client->getAccessToken()){
            try{
                /** 
                 * [$service [instance of Google_Service_YouTube ]]
                 * @var [type]
                 */             
                $youtube = new \Google_Service_YouTube($this->client);

                $videoId = $videoId;
                $imagePath = $url;

                /**
                 * size of chunk to be uploaded  in bytes [default  1 * 1024 * 1024] (Set a higher value for reliable connection as fewer chunks lead to faster uploads)
                 */             
                $chunkSizeBytes = 1 * 1024 * 1024;
                $this->client->setDefer(true);

                /**
                 * Setting the defer flag to true tells the client to return a request which can be called with ->execute(); instead of making the API call immediately
                 */
                $setRequest = $youtube->thumbnails->set($videoId);

                /**
                 * MediaFileUpload object [resumable uploads]
                 */
                $media = new \Google_Http_MediaFileUpload(
                    $this->client,
                    $setRequest,
                    'image/png',
                    null,
                    true,
                    $chunkSizeBytes
                );
                $media->setFileSize(filesize($imagePath));

                /** 
                 * Read the media file [to upload chunk by chunk]
                 */
                $status = false;
                $handle = fopen($imagePath, "rb");
                while (!$status && !feof($handle)) {
                  $chunk = fread($handle, $chunkSizeBytes);
                  $status = $media->nextChunk($chunk);
                }

                fclose($handle);

                /**
                 * set defer to false [to make other calls after the file upload]
                 */
                $this->client->setDefer(false);
                $thumbnailUrl = $status['items'][0]['default']['url'];
                return $thumbnailUrl;

            } catch( \Google_Exception $e ) {

                throw new ServerErrorHttpException($e->getMessage(), 1);
            }
        }
    }

    /** 
     * [updateTags description]
     * @param  [type] $videoId   [eventID]
     * @param  array  $tagsArray [array of tags]
     */
    public function updateTags($videoId, $tagsArray = [])
    {
        if($this->client->getAccessToken()){
            try {

                /** 
                 * [$service [instance of Google_Service_YouTube ]]
                 * @var [type]
                 */             
                $youtube = new \Google_Service_YouTube($this->client);
                $videoId = $videoId;

                /**
                 * [$listResponse videos.list method to retrieve the video resource.]
                 */
                $listResponse = $youtube->videos->listVideos("snippet",
                array('id' => $videoId));
                $video = $listResponse[0];

                $videoSnippet = $video['snippet'];
                $videoSnippet['tags'] = $data["tag_array"];             
                $video['snippet'] = $videoSnippet;

                /**
                 * [$updateResponse calling the videos.update() method.]
                 */
                $updateResponse = $youtube->videos->update("snippet", $video);

            } catch( \Google_Exception $e ){
                throw new ServerErrorHttpException($e->getMessage(), 1);
            }
        }
    }

    /**
     * [transitionEvent transition the state of event [test, start streaming , stop streaming]]
     * @param  [type] $token            [auth token for the channel]
     * @param  [type] $youtube_event_id [eventId]
     * @param  [type] $broadcastStatus  [transition state - ["testing", "live", "complete"]]
     * @return [type]                   [transition status]
     */
    public function transitionEvent($token, $youtube_event_id, $broadcastStatus)
    {
        try{

            if( empty($token) ){
                Yii::info("token can't be empty");
                return false;
            }

            /** 
             * [setAccessToken [setting accent token to client]]
             */                 
            $setAccessToken = self::setAccessToken($token);         
            if(!$setAccessToken)
                return false;   

            $part = "status, id, snippet";
            /** 
             * [$service [instance of Google_Service_YouTube ]]
             * @var [type]
             */         
            $youtube = new \Google_Service_YouTube($this->client);
            $liveBroadcasts = $youtube->liveBroadcasts;
            $transition = $liveBroadcasts->transition($broadcastStatus, $youtube_event_id, $part);
            return $transition;

        } catch( \Google_Exception $e ) {

            throw new Exception($e->getMessage(), 1);

        } catch(\Exception $e){

            throw new ServerErrorHttpException($e->getMessage(), 1);
        }
    }

    /**
     * [updateBroadcast update the already created event on youtunbe channel]
     * @param  [type] $token            [channel auth token]
     * @param  [type] $data             [event details]
     * @param  [type] $youtube_event_id [eventID]
     * @return [type]                   [response array for various process in the update]
     */
    public function updateBroadcast($token, $data, $youtube_event_id)
    {
        try{                
            /** 
             * [setAccessToken [setting accent token to client]]
             */                 
            $setAccessToken = self::setAccessToken($token);
            if(!$setAccessToken)
                return false;   
            /** 
             * [$service [instance of Google_Service_YouTube ]]
             * @var [type]
             */         
            $youtube = new \Google_Service_YouTube($this->client);
            
            if(count($data)<1 || empty($data)) {
                Yii::info('request_data is empty');
                return false;
            }

            $title = $data["title"];
            $description = $data['description'];
            $thumbnail_path = isset($data['thumbnail_path']) ? $data['thumbnail_path'] : null;
            
            /**
             *  parsing event start date
             */
            $startdt = Carbon::createFromFormat('Y-m-d H:i:s', $data['event_start_date_time'], $data['time_zone']);
            $startdt = ($startdt < Carbon::now($data['time_zone'])) ? Carbon::now($data['time_zone']) : $startdt;
            $startdtIso = $startdt->toIso8601String();
            $privacy_status = isset($data['privacy_status']) ? $data['privacy_status'] : "public";

            /**
             * parsing event end date
             */
            if (isset($data['event_end_date_time'])) {
                $enddt = Carbon::createFromFormat('Y-m-d H:i:s', $data['event_end_date_time'], $data['time_zone']);
                $enddt = ($enddt < Carbon::now($data['time_zone'])) ? Carbon::now($data['time_zone']) : $enddt;
                $enddtIso = $enddt->toIso8601String();              
            }

            $tags = substr(str_replace(", ,", ",", implode(',', $data['tag_array'])),0,498);
            $tags = (substr($tags, -1)==',') ? substr($tags,0,-1) : $tags;
            $data['tag_array']  = explode(',', $tags);
            
            $language = $data['language_name'];

            /**
             * Create an object for the liveBroadcast resource's snippet [snippet's title, scheduled start time, and scheduled end time.]
             */
            $this->googleLiveBroadcastSnippet->setTitle($title);
            $this->googleLiveBroadcastSnippet->setDescription($description);
            $this->googleLiveBroadcastSnippet->setScheduledStartTime($startdtIso);
            
            if (isset($data['event_end_date_time'])) 
                $this->googleLiveBroadcastSnippet->setScheduledEndTime($enddtIso);
            
            /** 
             * Create an object for the liveBroadcast resource's status ["private, public or unlisted".]
             */
            $this->googleLiveBroadcastStatus->setPrivacyStatus($privacy_status);

            /**
             * Create the API request  [inserts the liveBroadcast resource.]
             */
            $this->googleYoutubeLiveBroadcast->setSnippet($this->googleLiveBroadcastSnippet);
            $this->googleYoutubeLiveBroadcast->setStatus($this->googleLiveBroadcastStatus);
            $this->googleYoutubeLiveBroadcast->setKind('youtube#liveBroadcast');
            $this->googleYoutubeLiveBroadcast->setId($youtube_event_id);

            /** 
             * Execute the request [return info about the new broadcast ]
             */
            $broadcastsResponse = $youtube->liveBroadcasts->update('snippet,status',
                $this->googleYoutubeLiveBroadcast, array());


            /**
             * set thumbnail
             */
            if(!is_null($thumbnail_path)){
                $thumb = self::uploadThumbnail($thumbnail_path, $youtube_event_id);
            }

            /** 
             * Call the API's videos.list method [retrieve the video resource]
             */
            $listResponse = $youtube->videos->listVideos("snippet",
            array('id' => $youtube_event_id));
            $video = $listResponse[0]; 
            $videoSnippet = $video['snippet'];
            $videoSnippet['tags'] = $data['tag_array'];   

            /** 
             * set Language and other details
             */
            if(!is_null($language)){
                $temp = isset($this->yt_language[$language]) ? $this->yt_language[$language] : "en"; 
                $videoSnippet['defaultAudioLanguage'] = $temp; 
                $videoSnippet['defaultLanguage'] = $temp;  
            }

            $videoSnippet['title'] = $title; 
            $videoSnippet['description'] = $description; 
            $videoSnippet['scheduledStartTime'] = $startdtIso; 
            $video['snippet'] = $videoSnippet;

            /** 
             * Update the video resource  [call videos.update() method]
             */
            $updateResponse = $youtube->videos->update("snippet", $video);

            $response['broadcast_response'] = $updateResponse;

            $youtube_event_id = $updateResponse['id'];

            Yii::info('----------- Update BROADCAST RESPONSE ------------');
            Yii::info(json_encode($updateResponse));

            $this->googleYoutubeLiveStreamSnippet->setTitle($title);

                        /**
             * object for content distribution  [stream's format,ingestion type.]
             */

            $this->googleYoutubeCdnSettings->setFormat("720p");
            $this->googleYoutubeCdnSettings->setIngestionType('rtmp');

            /** 
             * API request [inserts liveStream resource.]
             */
            $this->googleYoutubeLiveStream->setSnippet($this->googleYoutubeLiveStreamSnippet);
            $this->googleYoutubeLiveStream->setCdn($this->googleYoutubeCdnSettings);
            $this->googleYoutubeLiveStream->setKind('youtube#liveStream');

            /**
             * execute the insert request [return an object that contains information about new stream]
             */
            $streamsResponse = $youtube->liveStreams->insert('snippet,cdn', $this->googleYoutubeLiveStream, array());
            $response['stream_response'] = $streamsResponse;

            /**
             * Bind the broadcast to the live stream
             */
            $bindBroadcastResponse = $youtube->liveBroadcasts->bind(
                $updateResponse['id'],'id,contentDetails',
                array(
                    'streamId' => $streamsResponse['id'],
                ));

            $response['bind_broadcast_response'] = $bindBroadcastResponse;

            return $response;

        } catch ( \Google_Service_Exception $e ) {

            throw new ServerErrorHttpException($e->getMessage(), 1);

        } catch ( \Google_Exception $e ) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        
        } catch(\Exception $e) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        }

    }

    /** 
     * [deleteEvent delete an event created in youtube]
     * @param  [type] $token            [auth token for channel]
     * @param  [type] $youtube_event_id [eventID]
     * @return [type]                   [deleteBroadcastsResponse]
     */
    public function deleteEvent($token, $youtube_event_id)
    {
        try {
            /** 
             * [setAccessToken [setting accent token to client]]
             */                 
            $setAccessToken = self::setAccessToken($token);
            if(!$setAccessToken)
                return false;   
            /** 
             * [$service [instance of Google_Service_YouTube ]]
             * @var [type]
             */         
            $youtube = new \Google_Service_YouTube($this->client);
            $deleteBroadcastsResponse = $youtube->liveBroadcasts->delete($youtube_event_id);
            
            return $deleteBroadcastsResponse;
                
        } catch ( \Google_Service_Exception $e ) {

            throw new ServerErrorHttpException($e->getMessage(), 1);

        } catch ( \Google_Exception $e ) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        
        } catch(\Exception $e) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        }
    }

}
