<?php 
namespace soareseneves\youtubeapi;

use Yii;
use yii\web\ServerErrorHttpException;

/**
*  Api Service For Auth
*/
class AuthService 
{	
	protected $client;
	protected $module;

	public function __construct()
	{
		$this->module = \Yii::$app->getModule('youtubeapi');
		$this->client = new \Google_Client;

		$this->client->setClientId($this->module->client_id);
        $this->client->setClientSecret($this->module->client_secret);
        $this->client->setDeveloperKey($this->module->api_key);
        $this->client->setRedirectUri($this->module->redirect_url);

		$this->client->setScopes([
		                             'https://www.googleapis.com/auth/youtube',
		                         ]);
		$this->client->setAccessType('offline');
		$this->client->setPrompt('consent');
	}

	/**	
	 * [getToken -generate token from response code recived on visiting the login url generated]
	 * @param  [type] $code [code for auth]
	 * @return [type]       [authorization token]
	 */
	public function getToken($code)
	{
		try {
			
			$this->client->authenticate($code);
			$token = $this->client->getAccessToken();
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
				$this->client->setState($channelId);

			$this->client->setLoginHint($youtube_email);
			$authUrl = $this->client->createAuthUrl();
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