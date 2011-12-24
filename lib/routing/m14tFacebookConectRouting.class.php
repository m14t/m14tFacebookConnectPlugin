<?php
/**
 *
 * @package m14tFacebookConectPlugin
 * @subpackage routing
 * @author Matt Farmer <matt@useallfive.com>
 *
 */
class m14tFacebookConectRoutingHelper
{
  /**
   * Load routes
   *
   * @param sfEvent $event
   */
  public static function listenToLoadConfigurationEvent(sfEvent $event)
  {
    $routing = $event->getSubject();

    /*
     * Request Authorization
     */
    $routing->prependRoute('m14t_facebook_request_authorization', new sfRoute('/m14tFacebookConnect/authorize', array(
      'module' => 'm14tFacebookConnect',
      'action' => 'Authorize',
    )));
  }
}
