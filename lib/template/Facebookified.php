<?php

class Doctrine_Template_Facebookified extends Doctrine_Template {

  protected
    $_options = array(
      'fields' => array(
        //FB Column  => sfGuardUser Column
        'id'         => 'facebook_uid',
        'username'   => 'username',
        'first_name' => 'first_name',
        'last_name'  => 'last_name',
      ),
    );


  /**
   * Constructor for Facebookified Template
   *
   * @param array $options
   * @return void
   * @author Matt Farmer <work@mattfarmer.net>
   */
  public function __construct(array $options = array()) {
    $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
  }


  public function hasFacebook() {
    $sfGuardUser = $this->getInvoker();
    return "" != $sfGuardUser['facebook_auth_code'];
  }


  public function populateWithFacebook(m14tFacebook $fb, $force = true) {
    $sfGuardUser = $this->getInvoker();

    foreach ( $this->_options['fields'] as $fb_key => $sfg_key ) {
      $func = 'get'.sfInflector::camelize($fb_key);
      switch ( $fb_key ) {
        case 'location':
        case 'hometown':
        case 'languages':
        case 'education':
          $sfGuardUser->$sfg_key($fb->$func(), $force);
          break;
        case 'birthday':
          if ( $force || "" == $sfGuardUser[$sfg_key]) {
            $sfGuardUser[$sfg_key] = date('Y-m-d', strtotime($fb->$func()));
          }
          break;
        default:
          if ( $force || "" == $sfGuardUser[$sfg_key]) {
            $sfGuardUser[$sfg_key] = $fb->$func();
          }
          break;
      }
    }
  }


}
