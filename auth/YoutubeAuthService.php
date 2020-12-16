<?php 
namespace soareseneves\livesapi\auth;

use Yii;
use yii\web\ServerErrorHttpException;

/**
*  Api Service For Auth
*/
class YoutubeAuthService 
{	
	protected $googleClient;
	protected $module;

	public function __construct()
	{
		$this->module = \Yii::$app->getModule('livesapi');
		$this->googleClient = new \Google_Client;

		$this->googleClient->setClientId($this->module->providers->youtube->client_id);
        $this->googleClient->setClientSecret($this->module->providers->youtube->client_secret);
        $this->googleClient->setDeveloperKey($this->module->providers->youtube->api_key);
        $this->googleClient->setRedirectUri($this->module->providers->youtube->redirect_url);

		$this->googleClient->setScopes([
		                             'https://www.googleapis.com/auth/youtube.readonly',
                                    'https://www.googleapis.com/auth/youtube',
                                    'https://www.googleapis.com/auth/youtube.force-ssl'
		                         ]);
		$this->googleClient->setAccessType('offline');
		$this->googleClient->setPrompt('consent');
	}

	/**	
	 * [getToken -generate token from response code recived on visiting the login url generated]
	 * @param  [type] $code [code for auth]
	 * @return [type]       [authorization token]
	 */
	public function getToken($code)
	{
		try {
			
			$this->googleClient->authenticate($code);
			$token = $this->googleClient->getAccessToken();
			return $token;

		} catch ( \Google_Service_Exception $e ) {
			Yii::info('start calculating average revenue');
			Yii::info('--------- GOOGLE SERVICE EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));
			throw new ServerErrorHttpException($e->getMessage());

		} catch ( \Google_Exception $e ) {

			Yii::info('--------- GOOGLE EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));
			throw new ServerErrorHttpException($e->getMessage());
		} catch ( \Exception $e ) {

			Yii::info('--------- GOOGLE EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));
			throw new ServerErrorHttpException($e->getMessage());
		} 
	}

	/**
	 * [getLoginUrl - generates the url login url to generate auth token]
	 * @param  [type] $youtube_email [account to be authenticated]
	 * @param  [type] $channelId     [return identifier]
	 * @return [type]                [auth url to generate]
	 */
	public function getLoginUrl( $youtube_email, $channelId = null )
	{	
		try
		{	
			if(!empty($channelId))
				$this->googleClient->setState($channelId);

			$this->googleClient->setLoginHint($youtube_email);
			$authUrl = $this->googleClient->createAuthUrl();
			return $authUrl;

		} catch ( \Google_Service_Exception $e ) {

			Yii::info('--------- GOOGLE SERVICE EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));

			throw new ServerErrorHttpException($e->getMessage());

		} catch ( \Google_Exception $e ) {

			Yii::info('--------- GOOGLE EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));

			throw new ServerErrorHttpException($e->getMessage());
		} catch ( \Exception $e ) {

			Yii::info('--------- GOOGLE EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));

			throw new ServerErrorHttpException($e->getMessage());
		} 
		
	}

}