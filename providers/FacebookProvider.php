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

    public function broadcast($token, $data = null)
    {
        try{

            $title = $data['title'];
            $description = $data['description'];
            $startdt = Carbon::createFromFormat('Y-m-d H:i:s', $data["planned_start_time"], $data["time_zone"]);
            $startdt = ($startdt < Carbon::now($data["time_zone"])) ? Carbon::now($data["time_zone"]) : $startdt;
            $startdt = $startdt->timestamp;

            // Returns a `FacebookFacebookResponse` object
            $response = $this->facebookClient->post('/' . $token['user']['id'] . '/live_videos', array('enable_backup_ingest' => 'true', 'title' => $title, 'description' => $description, 'status' => 'SCHEDULED_UNPUBLISHED', 'planned_start_time' => $startdt), $token['access_token']);
           
            $graphNode = $response->getGraphNode();

            return $graphNode;
        } catch(FacebookExceptionsFacebookResponseException $e) {
            Yii::info('Graph returned an error: ' . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage(), 1);
        } catch(FacebookExceptionsFacebookSDKException $e) {
            Yii::info('Facebook SDK returned an error: ' . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage(), 1);
        } catch(\Exception $e) {
            throw new ServerErrorHttpException($e->getMessage(), 1);
        }

    }

    public function updateBroadcast($token, $data, $facebook_event_id)
    {
        try{
            $title = $data['title'];
            $description = $data['description'];
            $startdt = Carbon::createFromFormat('Y-m-d H:i:s', $data["planned_start_time"], $data["time_zone"]);
            $startdt = ($startdt < Carbon::now($data["time_zone"])) ? Carbon::now($data["time_zone"]) : $startdt;
            $startdt = $startdt->timestamp;

            // Returns a `FacebookFacebookResponse` object
            $response = $this->facebookClient->post('/' . $facebook_event_id, array('enable_backup_ingest' => 'true', 'title' => $title, 'description' => $description, 'status' => 'SCHEDULED_UNPUBLISHED', 'planned_start_time' => $startdt), $token['access_token']);
          
            $graphNode = $response->getGraphNode();

            return $graphNode;
        } catch(FacebookExceptionsFacebookResponseException $e) {
            Yii::info('Graph returned an error: ' . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage(), 1);
        } catch(FacebookExceptionsFacebookSDKException $e) {
            Yii::info('Facebook SDK returned an error: ' . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage(), 1);
        } catch(\Exception $e) {
            throw new ServerErrorHttpException($e->getMessage(), 1);
        }
    }

    public function deleteEvent($token, $facebook_event_id)
    {
        try{
            // Returns a `FacebookFacebookResponse` object
            $response = $this->facebookClient->delete('/' . $facebook_event_id, array(), $token['access_token']);
          
            $graphNode = $response->getGraphNode();

            return $graphNode;
        } catch(FacebookExceptionsFacebookResponseException $e) {
            Yii::info('Graph returned an error: ' . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage(), 1);
        } catch(FacebookExceptionsFacebookSDKException $e) {
            Yii::info('Facebook SDK returned an error: ' . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage(), 1);
        } catch(\Exception $e) {
            throw new ServerErrorHttpException($e->getMessage(), 1);
        }
    }

}


?>