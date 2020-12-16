<?php 
namespace soareseneves\livesapi\auth;

use Yii;
use yii\web\ServerErrorHttpException;

/**
*  Api Service For Auth
*/
class FacebookAuthService 
{	
	protected $facebookClient;
	protected $helper;
	protected $module;

	public function __construct()
	{
		$this->module = \Yii::$app->getModule('livesapi');
		$this->facebookClient = new \Facebook\Facebook([
        	'app_id' => $this->module->facebook->app_id,
        	'app_secret' => $this->module->facebook->app_secret,
        	'default_graph_version' => $this->module->facebook->api_version,
        ]);
        $this->helper = $this->facebookClient->getRedirectLoginHelper();
	}

	/**	
	 * [getToken -generate token from response code recived on visiting the login url generated]
	 * @param  [type] $code [code for auth]
	 * @return [type]       [authorization token]
	 */
	public function getToken($code)
	{
			try {
				$accessToken = $this->helper->getAccessToken();

				if (! isset($accessToken)) {
				  if ($helper->getError()) {
				    header('HTTP/1.0 401 Unauthorized');
				    echo "Error: " . $this->helper->getError() . "\n";
				    echo "Error Code: " . $this->helper->getErrorCode() . "\n";
				    echo "Error Reason: " . $this->helper->getErrorReason() . "\n";
				    echo "Error Description: " . $this->helper->getErrorDescription() . "\n";
				  } else {
				  	header('HTTP/1.0 400 Bad Request');
				    echo 'Bad request';
				  }
				  exit;
				}

				// Logged in
				//echo '<h3>Access Token</h3>';
				//var_dump($accessToken->getValue());

				// The OAuth 2.0 client handler helps us manage access tokens
				$oAuth2Client = $this->facebookClient->getOAuth2Client();

				// Get the access token metadata from /debug_token
				$tokenMetadata = $oAuth2Client->debugToken($accessToken);
				//echo '<h3>Metadata</h3>';
				//var_dump($tokenMetadata);

				// Validation (these will throw FacebookSDKException's when they fail)
				$tokenMetadata->validateAppId($this->module->facebook->app_id);
				// If you know the user ID this access token belongs to, you can validate it here
				//$tokenMetadata->validateUserId('123');
				$tokenMetadata->validateExpiration();

				if (! $accessToken->isLongLived()) {
				  // Exchanges a short-lived access token for a long-lived one
				  try {
				    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
				  } catch (Facebook\Exception\SDKException $e) {
				    echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
				    exit;
				  }

				  //echo '<h3>Long-lived</h3>';
				  //var_dump($accessToken->getValue());
				}

				return (string)$accessToken;
			} catch(Facebook\Exception\ResponseException $e) {
			  	// When Graph returns an error
			  	Yii::info('--------- FACEBOOK EXCEPTION ------------');
				Yii::info(json_encode($e->getMessage()));
				throw new ServerErrorHttpException($e->getMessage());
			} catch(Facebook\Exception\SDKException $e) {
				// When Facebook SDK returns an error
				Yii::info('--------- FACEBOOK EXCEPTION ------------');
				Yii::info(json_encode($e->getMessage()));
				throw new ServerErrorHttpException($e->getMessage());
			} catch ( \Exception $e ) {
				Yii::info('--------- FACEBOOK EXCEPTION ------------');
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
	public function getLoginUrl()
	{	
		try
		{	
            $permissions = ['email', 'publish_video', 'pages_manage_posts', 'pages_read_engagement'];
            $authUrl = $this->helper->getLoginUrl($this->module->facebook->redirect_url, $permissions);

			return $authUrl;
		} catch ( \Exception $e ) {

			Yii::info('--------- FACEBOOK EXCEPTION ------------');
			Yii::info(json_encode($e->getMessage()));

			throw new ServerErrorHttpException($e->getMessage());
		} 
		
	}

}