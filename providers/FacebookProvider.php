<?php

namespace soareseneves\livesapi\providers;

use Yii;
use yii\base\InvalidParamException;
use Carbon\Carbon;
use yii\web\ServerErrorHttpException;
use Facebook\Facebook;

class FacebookProvider
{

	public $app_id;
    public $app_secret;
    public $api_version;
    public $redirect_url;
    public $fb_language;

    protected $facebookClient;

    public function __construct($params){
    	$this->app_id = $params['app_id'] ?: '';
    	$this->app_secret = $params['app_secret'] ?: '';
    	$this->api_version = $params['api_version'] ?: '';
    	$this->redirect_url = $params['redirect_url'] ?: '';
    	$this->fb_language = $params['fb_language'] ?: 'pt_BR';

        $this->facebookClient = new Facebook([
            'app_id' => $this->app_id,
            'app_secret' => $this->app_secret,
            'default_graph_version' => $this->api_version,
        ]);
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
            //
        } catch(\Exception $e) {

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
            //
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
             //              
        } catch(\Exception $e) {

            throw new ServerErrorHttpException($e->getMessage(), 1);
        }
    }

}


?>