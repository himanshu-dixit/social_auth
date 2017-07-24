<?php

namespace Drupal\social_post_facebook\Plugin\RulesAction;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\social_post_facebook\Plugin\Network\FacebookPostInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Tweet' action.
 *
 * @RulesAction(
 *   id = "social_post_facebook_tweet",
 *   label = @Translation("Tweet"),
 *   category = @Translation("Social Post"),
 *   context = {
 *     "status" = @ContextDefinition("string",
 *       label = @Translation("Tweet content"),
 *       description = @Translation("Specifies the status to post.")
 *     )
 *   }
 * )
 */
class Tweet extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The facebook post network plugin.
   *
   * @var \Drupal\social_post_facebook\Plugin\Network\FacebookPostInterface
   */
  protected $facebookPost;

  /**
   * The social post facebook entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facebookEntity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /* @var \Drupal\social_post_facebook\Plugin\Network\FacebookPost $facebook_post*/
    $facebook_post = $container->get('plugin.network.manager')->createInstance('social_post_facebook');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $facebook_post,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Tweet constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_post_facebook\Plugin\Network\FacebookPostInterface $facebook_post
   *   The facebook post network plugin.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              FacebookPostInterface $facebook_post,
                              EntityTypeManagerInterface $entity_manager,
                              AccountInterface $current_user) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->facebookPost = $facebook_post;
    $this->facebookEntity = $entity_manager->getStorage('social_post_facebook_user');
    $this->currentUser = $current_user;
  }

  /**
   * Executes the action with the given context.
   *
   * @param string $status
   *   The tweet text.
   */
  protected function doExecute($status) {
    $accounts = $this->getFacebookAccountsByUserId($this->currentUser->id());

    /* @var \Drupal\social_post_facebook\Entity\FacebookUserInterface $account */
    foreach ($accounts as $account) {
      // Update status, If there was an error, boolean FALSE is returned.
      if (!$this->facebookPost->doPost($account->getAccessToken(), $account->getAccessTokenSecret(), $status)) {
        drupal_set_message('There was an error while updating Facebook status for ' . $account->getScreenName() . ', please review the logs.', 'error');
      }
    }
  }

  /**
   * Gets all the accounts associated to a user id.
   *
   * @param int $user_id
   *   The user id.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array with the accounts.
   */
  protected function getFacebookAccountsByUserId($user_id) {
    $accounts = $this->facebookEntity->loadByProperties([
      'uid' => $user_id,
    ]);

    return $accounts;
  }

}
