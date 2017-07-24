<?php

namespace Drupal\social_post_facebook\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Drupal\social_post_facebook\Settings\FacebookPostSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use League\OAuth2\Client\Provider\Facebook;

/**
 * Defines Social Post Facebook Network Plugin.
 *
 * @Network(
 *   id = "social_post_facebook",
 *   social_network = "Facebook",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_facebook\Settings\FacebookPostSettings",
 *        "config_id": "social_post_facebook.settings"
 *      }
 *   }
 * )
 */
class FacebookPost extends SocialPostNetwork implements FacebookPostInterface {

  use LoggerChannelTrait;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Facebook connection.
   *
   * @var FacebookOAuth
   */
  protected $connection;

  /**
   * The tweet text.
   *
   * @var string
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * FacebookPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\Facebook
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\League\OAuth2\Client\Provider\Facebook';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Facebook Library for the league oAuth not found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId'          => $settings->getAppId(),
        'clientSecret'      => $settings->getAppSecret(),
        'redirectUri'       => $GLOBALS['base_url'] . '/user/login/facebook/callback',
        'graphApiVersion'   => 'v' . $settings->getGraphVersion(),
      ];

      return new Facebook($league_settings);
    }
    return FALSE;
  }


  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings
   *   The Facebook auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(FacebookPostSettings  $settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    $graph_version = $settings->getGraphVersion();

    if (!$app_id || !$app_secret || !$graph_version) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function post() {
    if (!$this->connection) {
      throw new SocialApiException('Call post() method from its wrapper doPost()');
    }

    $post = $this->connection->post('statuses/update', ['status' => $this->status]);

    if (isset($post->error)) {
      $this->getLogger('social_post_facebook')->error($post->error);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($access_token, $access_token_secret, $status) {
    $this->connection = $this->getSdk2($access_token, $access_token_secret);
    $this->status = $status;
    return $this->post();
  }

}

