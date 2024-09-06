<?php
namespace Drupal\user_ip_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'User IP Block' block.
 *
 * @Block(
 *   id = "user_ip_block",
 *   admin_label = @Translation("User IP Block"),
 * )
 */
class UserIpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UserIpBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve the user's IP address.
    $ip_address = \Drupal::request()->getClientIp();

    // Get the current server time.
    $server_time = date('Y-m-d H:i:s');

    // Get the current user information.
    $current_user = \Drupal::currentUser();
    $user_entity = $this->entityTypeManager->getStorage('user')->load($current_user->id());

    // Initialize credits variable.
    $credits = [];

    // Check if the user entity and field exist.
    if ($user_entity && $user_entity->hasField('field_user_credits')) {
      $credit_references = $user_entity->get('field_user_credits')->getValue();
      
      // Load the referenced entities by ID.
      foreach ($credit_references as $credit_reference) {
        $credit_entity = $this->entityTypeManager->getStorage('point')->load($credit_reference['target_id']);
        
        if ($credit_entity) {
          // Access the 'points' field value.
          $points_field = $credit_entity->get('points')->getValue();
          if (!empty($points_field) && isset($points_field[0]['value'])) {
            $credits[] = $points_field[0]['value'];
          }
          // Use dpm() to dump the credit entity for debugging.
       
        }
      }
    }

    // Render the block content with the text-white class.
    return [
      '#markup' => $this->t('IP: @ip<br/>Server Time: @time<br/>Your Credit Balance: @credits', [
        '@ip' => $ip_address,
        '@time' => $server_time,
        '@credits' => !empty($credits) ? implode(', ', $credits) : $this->t('No credits available'),
      ]),
      '#attributes' => [
        'class' => ['text-white'],
      ],
    ];
  }

   /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
