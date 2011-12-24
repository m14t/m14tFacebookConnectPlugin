<?php

class m14tFacebookUser extends sfGuardSecurityUser {

  protected $m14tFacebook = null;

  public function getFacebook() {
    if ( is_null($this->m14tFacebook) ) {
      $this->m14tFacebook = new m14tFacebook($this->getFacebookAuthCode(), $this);
    }
    return $this->m14tFacebook;
  }

  public function hasFacebook() {
    if ( $sfGuardUser = $this->getGuardUser() ) {
      if ( $sfGuardUser->getFacebookAuthCode() ) {
        return true;
      }
    }
    return false;
  }

  public function isFacebookFriendsWith($user) {
    if ( $this->hasFacebook() ) {
      //-- Only check if we have linked with facebook
      if ( is_string($user) || is_numeric($user) ) {
        $fb_uid = $user;
      } else {
        //-- Assume its a sfGuardUser
        $fb_uid = $user->getFacebookUid();
        if ( ! $fb_uid ) {
          return false;
        }
      }

      $friends = $this->getFacebook()->getFriends();
      foreach( $friends as $friend ) {
        if ( $friend->id == $fb_uid) {
          return true;
        }
      }
    }
    return false;
  }

  public function populateFromFacebook($doSave = true) {
    $fb = $this->getFacebook();

    if ( !($sfGuardUser = $this->getGuardUser()) ) {
      if ( ($email = $fb->getEmail()) ) {
        //-- Emails must be uniqe so start there
        $sfGuardUser = Doctrine::getTable('sfGuardUser')->findOneByEmailAddress($email);
      }
      if ( !$sfGuardUser && ($fb_uid = $fb->getId()) ) {
        $sfGuardUser = Doctrine::getTable('sfGuardUser')->findOneByFacebookUid($fb_uid);
      }
      if ( ! $sfGuardUser ) {
        $sfGuardUser = new sfGuardUser();
      }
    }
    //-- If it is in the session but not the guard User, add it to the guard user
    if ( !$sfGuardUser->getFacebookAuthCode() &&
         $this->getAttribute('facebook_auth_code')
    ) {
      if (sfConfig::get('sf_logging_enabled')) {
        sfContext::getInstance()->getLogger()->info(
          '--- DEBUG --- FB AUTH CODE in session but not the guard User'
        );
      }
      $sfGuardUser->setFacebookAuthCode($this->getAttribute('facebook_auth_code'));
    }

    $sfGuardUser->populateWithFacebook($fb);

    if ( $doSave ) {
      $sfGuardUser->save();
      $this->signin($sfGuardUser);
    }

    //-- NOTE:  The $sfGuardUser MAY NOT be saved yet!
    $dispatcher = sfContext::getInstance()->getEventDispatcher();
    $dispatcher->notify(new sfEvent($sfGuardUser, 'm14tFacebook.connect', array(
    )));

    return $sfGuardUser;
  }

  public function hasFacebookAuthCode() {
    return $this->hasAttribute('facebook_auth_code');
  }

  public function setFacebookAuthCode($access_token) {
    $this->setAttribute('facebook_auth_code', $access_token);
    if ( $sfGuardUser = $this->getGuardUser() ) {
      $sfGuardUser->setFacebookAuthCode($access_token);
      $sfGuardUser->save();
    }
  }

  public function getFacebookAuthCode($force = false) {
    //-- Try the session
    $auth_code = $this->getAttribute('facebook_auth_code');
    if ( !$auth_code || $force ) {
      //-- Try the guard user
      if ( !($sfGuardUser = $this->getGuardUser()) ||
           !($auth_code = $sfGuardUser->getFacebookAuthCode()) ||
           $force
      ) {
        //-- No where to be found -- Request a new one
        $context = sfContext::getInstance();
        $routing = $context->getRouting();
        $controller = $context->getController();

        $redirect = 'http'. (empty($_SERVER['HTTPS'])?'':'s'). '://'.
                    $_SERVER['HTTP_HOST'] .
                    $_SERVER['REQUEST_URI'];

        $url = $controller->genUrl(
                 'm14t_facebook_request_authorization',
                 true
               ).'?'.
               http_build_query(array('redirect-url' => $redirect));
        $controller->redirect($url);
      }
    }
    return $auth_code;
  }

}
