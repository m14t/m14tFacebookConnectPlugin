<?php

/**
 *
 * @package    m14tFacebookConnectPlugin
 * @author     Matt Farmer <matt@useallfive.com>
 *
 */
class m14tFacebook {

  protected
    $access_token = null,
    $user = null,
    $data = array();

  public function __construct($access_token, $user) {
    $this->access_token = $access_token;
    $this->user = $user;
  }

  public function fieldIsConnection($field) {
    return in_array($field, array('friends'));
  }

  public function getGraphData($field) {

    if ( $this->fieldIsConnection($field) ) {
      $graph_url = "https://graph.facebook.com/me/". $field ."?". $this->access_token;
    } else {
      $field = "me";
      $graph_url = "https://graph.facebook.com/me?". $this->access_token;
    }

    if ( ! array_key_exists($field, $this->data) ) {
      $session = curl_init($graph_url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $res = json_decode(curl_exec($session));
      if ( $res && array_key_exists('error', $res) ) {
        $err = $res->error;
        self::log("ERROR: ". $err->type .": ". $err->message);
        $this->user->getFacebookAuthCode(true);
        return false;
      }
      $this->data[$field] = $res;
      if ( $this->fieldIsConnection($field) ) {
        $this->data[$field] = $this->data[$field]->data;
      }
    }

    if ( $this->fieldIsConnection($field) ) {
      return $this->data;
    } else {
      return $this->data['me'];
    }
  }

  public function __call($func, $args) {
    //-- Add Generic get* metonds
    switch( substr($func, 0, 3) ) {
    case 'get':
      $field = sfInflector::underscore(substr($func, 3));
      if ( $data = $this->getGraphData($field) ) {
        if ( array_key_exists( $field, $data ) ) {
          if ( is_array($data) ) {
            return $data[$field];
          } else {
            return $data->$field;
          }
        } else {
          return null;
        }
      }
      break;
    default:
      throw new Exception('Fatal Error: Call to undefined method '. __CLASS__ .'::'. $func .'() in '. __FILE__ .'  on '. __LINE__);
      break;
    }
  }

  /**
   * get the facebook api key
   *
   * @return string
   */
  public static function getApiKey() {
    $api_key = sfConfig::get('app_facebook_api_key', "xxx");
    if ( "xxx" == $api_key ) {
      throw new Exception('{m14tFacebookConnect} No API Key set!');
    }
    return $api_key;
  }

  /**
   * get the facebook app secret
   *
   * @return string
   */
  public static function getAppSecret()
  {
    $app_secret = sfConfig::get('app_facebook_app_secret', "xxx");
    if ( "xxx" == $app_secret ) {
      throw new Exception('{m14tFacebookConnect} No App Secret set!');
    }
    return $app_secret;
  }

  /**
   * get the facebook app id
   *
   * @return integer
   */
  public static function getAppId()
  {
    $app_id = sfConfig::get('app_facebook_app_id', "xxx");
    if ( "xxx" == $app_id ) {
      throw new Exception('{m14tFacebookConnect} No App ID set!');
    }
    return $app_id;
  }


  public static function getScope()
  {
    return sfConfig::get('app_facebook_scope');
  }

  public static function getAuthCodeUrl($redirect_uri) {
    $app_id = self::getAppId();
    $scope = self::getScope();

    $base_uri = "https://www.facebook.com/dialog/oauth";
    $params = array(
      'client_id' => $app_id,
      'redirect_uri' => $redirect_uri,
    );
    if ( $scope ) {
      $params['scope'] = $scope;
    }
    $url = "$base_uri?".http_build_query($params);

    self::log("AuthCodeUrl: $url");

    return $url;
  }

  public static function getAccessToken($authorization_code, $redirect_uri) {

    self::log("Authorization token: $authorization_code");

    $app_id = self::getAppId();
    $app_secret = self::getAppSecret();

    $base_uri = "https://graph.facebook.com/oauth/access_token";
    $token_url = "$base_uri?".http_build_query(array(
      'client_id' => $app_id,
      'redirect_uri' => $redirect_uri,
      'client_secret' => $app_secret,
    ))."&code=$authorization_code";

    self::log("Token URL: $token_url");

    $session = curl_init($token_url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $access_token = curl_exec($session);

    return $access_token;
  }

  public function createPost($params = array(), $profile_id = null ) {
    if ( is_null($profile_id) ) {
      $profile_id = "me";
    }

    $allowed_params = array(
      'message',
      'link',
      'picture',
      'name',
      'caption',
    );
    foreach ( $params as $k => $v ) {
      if ( !in_array($k, $allowed_params) ) {
        throw new Exception("{m14tFacebookConnect} '$k' is not a valid parameter to be passed to createPost!");
      }
    }

    $params['access_token'] = substr($this->access_token, 13);
    $params['privacy'] = '{"value": "ALL_FRIENDS"}';

    $graph_url = "https://graph.facebook.com/". $profile_id ."/feed";
    $session = curl_init($graph_url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_exec($session);
  }


  public static function log($msg, $level = 'info') {
    if (sfConfig::get('sf_logging_enabled')) {
      sfContext::getInstance()->getLogger()->$level("{m14tFacebookConnect} ".$msg);
    }
  }

}
