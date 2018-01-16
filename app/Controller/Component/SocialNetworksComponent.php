<?php

App::uses('Component', 'Controller');
App::uses('TwitterToken', 'Model');

require_once(APP . 'Lib/OAuth.php');
require_once(APP . 'Lib/TwitterOAuth.php');

class SocialNetworksComponent extends Component {

    public $components = array(
        'Session',
    );

    public function initialize(Controller $controller)
    {
        parent::initialize($controller);

        $this->TwitterToken = ClassRegistry::init('TwitterToken');
    }

    public function loginToFacebookWithCookie($cookie) {

        foreach ($cookie as $key => $val){
            $_COOKIE[$key] = $val;
        }

        $config = Configure::read('Facebook');
        $facebookOperator = new Facebook\Facebook($config);
        $facebookHelper = $facebookOperator->getJavaScriptHelper();

        try {
            $accessToken = $facebookHelper->getAccessToken();
            if ($accessToken == NULL) {
                throw new Exception('No cookies');
            }
            $facebookAccount = $facebookOperator->get('/me?fields=email,name,gender,first_name,last_name,picture.width(300)', $accessToken->getValue());
            $facebookAccountData = $facebookAccount->getGraphUser();
        } catch(Exception $exception) {
            throw $exception;
        }

        return $facebookAccountData;
    }

    public function getTwitterLoginUrl() {

        $config = Configure::read('Twitter');
        $appKey = $config['key'];
        $appSecret = $config['secret'];

        $twitterOperator = new TwitterOAuth(
            $appKey,
            $appSecret
        );
        $callbackUrl = $config['callback'];

        $requestToken = $twitterOperator->getRequestToken($callbackUrl);
        if (empty($requestToken)) {
            throw new Exception('Twitter login unavailable');
        }

        $token = $requestToken['oauth_token'];
        $secret = $requestToken['oauth_token_secret'];
        $this->Session->write('Twitter.request_token', $token);
        $this->Session->write('Twitter.request_token_secret', $secret);

        $loginUrl = $twitterOperator->getAuthorizeURL($token);

        try {
            $this->TwitterToken->createNew($token, $secret);
        } catch (Exception $exception) {
            throw $exception;
        }

        return $loginUrl;
    }

    public function getTwitterUser($token, $secret, $verifier) {

        $config = Configure::read('Twitter');
        $appKey = $config['key'];
        $appSecret = $config['secret'];

        $twitterOperator = new TwitterOAuth(
            $appKey,
            $appSecret,
            $token,
            $secret
        );

        $accessToken = $twitterOperator->getAccessToken($verifier);
        if ($accessToken == NULL) {
            throw new Exception('Twitter login unavailable');
        }

        $twitterOperator = new TwitterOAuth(
            $appKey,
            $appSecret,
            $accessToken['oauth_token'],
            $accessToken['oauth_token_secret']
        );

        $params = [
            'include_entities' => 'false',
            'skip_status' => 'true',
            'include_email' => 'true'
        ];

        $data = $twitterOperator->get('account/verify_credentials', $params);
        if ($data == NULL) {
            throw new Exception('Twitter login unavailable');
        }

        return array(
            'id' => $data->id,
            'name' => property_exists($data, 'name') ? $data->name : NULL,
            'email' => property_exists($data, 'email') ? $data->email : NULL,
            'picture' => property_exists($data, 'profile_image_url') ? $data->profile_image_url : NULL,
        );
    }

}