<?php

namespace Drupal\social_post_facebook\Plugin\Network;

use Drupal\social_post\Plugin\Network\SocialPostNetworkInterface;

/**
 * Defines an interface for Facebook Post Network Plugin.
 */
interface FacebookPostInterface extends SocialPostNetworkInterface {



  /**
   * Wrapper for post method.
   *
   * @param string $access_token
   *   The access token.
   * @param string $access_token_secret
   *   The access token secret.
   * @param string $status
   *   The tweet text.
   */
  public function doPost($access_token, $access_token_secret, $status);


}
