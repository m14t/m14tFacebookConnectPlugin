<?php

/*
 * Register routing
 */
if (sfConfig::get('app_m14t_facebook_connect_plugin_load_routing', true))
{
  $this->dispatcher->connect('routing.load_configuration', array('m14tFacebookConectRoutingHelper', 'listenToLoadConfigurationEvent'));
}
