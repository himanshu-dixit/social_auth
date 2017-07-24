<?php

namespace Drupal\social_post_facebook;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\social_post\PostManager;

/**
 * Manages the authorization process before getting a long lived access token.
 */
class FacebookPostAuthManager extends PostManager\PostManager {
  /**
   * The session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * FacebookPostManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to get the parameter code returned by Google.
   */
  public function __construct(Session $session, RequestStack $request) {
    $this->session = $session;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * Returns the OAuth Verifier provided by Facebook.
   *
   * @return string
   *   The oauth verifier string.
   */
  public function getOauthVerifier() {
    return $this->request->query->get('oauth_verifier');
  }

  /**
   * Sets the auth token.
   *
   * This oauth token is not the permanent access token for the user. This auth
   * token is the one returned by Facebook when we first request
   * /oauth/request_token to then get an oauth_verifier for authorization.
   *
   * @param string $oauth_token
   *   The oauth token.
   */
  public function setOauthToken($oauth_token) {
    $this->session->set('social_post_facebook_oauth_token', $oauth_token);
  }

  /**
   * Sets the auth token secret.
   *
   * This oauth token secret is not the permanent access token secret for the
   * user.This auth token is the one returned by Facebook when we first request
   * /oauth/request_token to then get an oauth_verifier for authorization.
   *
   * @param string $oauth_token_secret
   *   The oauth token secret.
   */
  public function setOauthTokenSecret($oauth_token_secret) {
    $this->session->set('social_post_facebook_oauth_token_secret', $oauth_token_secret);
  }

  /**
   * Gets the auth token.
   *
   * This oauth token is not the permanent access token for the user. This auth
   * token is the one returned by Facebook when we first request
   * /oauth/request_token to then get an oauth_verifier for authorization.
   *
   * @return string
   *   The oauth token.
   */
  public function getOauthToken() {
    return $this->session->get('social_post_facebook_oauth_token');
  }

  /**
   * Gets the auth token secret.
   *
   * This oauth token secret is not the permanent access token secret for the
   * user.This auth token is the one returned by Facebook when we first request
   * /oauth/request_token to then get an oauth_verifier for authorization.
   *
   * @return string
   *   The oauth token secret.
   */
  public function getOauthTokenSecret() {
    return $this->session->get('social_post_facebook_oauth_token_secret');
  }

}
