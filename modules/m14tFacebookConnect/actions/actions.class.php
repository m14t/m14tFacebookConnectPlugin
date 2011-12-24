<?php
/**
 * m14tFacebookConnect actions.
 *
 * @package    m14tFacebookConnect
 * @author     Matt Farmer <matt@useallfive.com>
 */
class m14tFacebookConnectActions extends sfActions
{

  public function executeAuthorize(sfWebRequest $request) {
    $user = $this->getUser();
    $authorization_code = $request->getParameter('code');

    if ( $request->hasParameter('ajax') ) {
      $user->setAttribute('ajax', true, 'm14tFacebookConnect');
    } else {
      $dest_url = $request->getParameter('redirect-url');
    }

    if ( !empty($dest_url) ) {
      //-- Save this for later
      $user->setAttribute('redirect-url', $dest_url);
    }

    $routing = sfContext::getInstance()->getRouting();
    $rule = $routing->getCurrentRouteName();
    $url = $this->getController()->genUrl($rule, true);
    $dest_url = $user->getAttribute('redirect-url');

    if ( $err = $request->getParameter('error') ) {
/*
      $error_description = $request->getParameter('error_description');
      echo $error_description ."<br />\n";
      echo $err; die();
*/
      //-- Don't ask for it with facebook again
      $dest_url = preg_replace('/with_facebook[^&]*(&|)/', '', $dest_url);

      if ( ! $user->getAttribute('ajax', false, 'm14tFacebookConnect') ) {
        //-- No ajax call back means redirect in the current window.
        $this->redirect($dest_url);
      }
      //-- Make sure we don't have a debug too bar
      $user->setAttribute('ajax', null, 'm14tFacebookConnect');
      sfConfig::set('sf_web_debug', false);
    }
    if ( empty($authorization_code) ) {
      //-- Our first stop, getting an authorization code
      $auth_code_url = m14tFacebook::getAuthCodeUrl($url);
      $this->redirect($auth_code_url);
    } else {
      $access_token = m14tFacebook::getAccessToken($authorization_code, $url);

      $user->setFacebookAuthCode($access_token);
      if ( $sfGuardUser = $user->getGuardUser() ) {
        $fb = $user->getFacebook();
        $sfGuardUser->setFacebookUid($fb->getId());
        $sfGuardUser->save();
      }

      if ( ! $user->getAttribute('ajax', false, 'm14tFacebookConnect') ) {
        if ( ! $dest_url ) {
          $dest_url = sfConfig::get(
            'app_facebook_success_signin_url',
            sfConfig::get(
              'app_sf_guard_plugin_success_signin_url',
              '@homepage'
            )
          );
        }
        $this->redirect($dest_url);
      }
      $user->setAttribute('ajax', null, 'm14tFacebookConnect');
      sfConfig::set('sf_web_debug', false);
    }

  }

}
