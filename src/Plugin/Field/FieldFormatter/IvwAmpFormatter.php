<?php

namespace Drupal\ivw_integration_amp\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\ivw_integration\IvwLookupServiceInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ivw_amp_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ivw_amp_formatter",
 *   module = "ivw_integration_amp",
 *   label = @Translation("AMP formatter"),
 *   field_types = {
 *     "ivw_integration_settings"
 *   }
 * )
 */
class IvwAmpFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The IVW lookup service interface.
   *
   * @var \Drupal\ivw_integration\IvwLookupServiceInterface
   */
  protected $ivwLookupService;

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token utility.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Constructs an IvwAmpFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\ivw_integration\IvwLookupServiceInterface $ivwLookupService
   *   The IVW lookup service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    IvwLookupServiceInterface $ivwLookupService,
    ConfigFactoryInterface $configFactory,
    Token $token
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings
    );
    $this->ivwLookupService = $ivwLookupService;
    $this->configFactory = $configFactory;
    $this->tokenService = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('ivw_integration.lookup'),
      $container->get('config.factory'),
      $container->get('token')
    );
  }

  /**
   * Helper callback that alters returned token replacements.
   *
   * This is needed as we always want "delivery" ("D") set to "2" with AMP,
   * but there is no way to configure this outside of the node / term config
   * hierarchy.
   *
   * @param array $replacements
   *   Replacements as an array.
   * @param array $data
   *   Data as an array.
   * @param array $options
   *   Options as an array.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   Bubbleable metadata or null.
   */
  public static function alterReplacements(
    array &$replacements,
    array $data = [],
    array $options = [],
    BubbleableMetadata $bubbleable_metadata = NULL
  ) {
    if (isset($replacements['[ivw:delivery]'])) {
      // Token calls the callback after escaping the replacements...
      $replacements['[ivw:delivery]'] = new HtmlEscapedText('2');
    }
    if (isset($replacements['[ivw:homepage]'])) {
      // On AMP, we do not wish to have any homepage, the
      // real homepage is not present there.
      $replacements['[ivw:homepage]'] = new HtmlEscapedText('2');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(
    FieldItemListInterface $items,
    $langcode = NULL
  ) {
    $elements = [];
    $domain = $this->configFactory
      ->get('ivw_integration_amp.settings')
      ->get('amp_analytics_infonline_domain');
    $module_path = drupal_get_path('module', 'ivw_integration_amp');

    // Core token devs don't like the concept of callable.
    // Getting the method of the static class as closure keeps
    // the callback overridable by subclasses.
    $class = new ReflectionClass(static::class);
    $callback = $class->getMethod('alterReplacements')->getClosure();

    foreach ($items as $delta => $item) {
      $st = $this->configFactory->get('ivw_integration.settings')->get('mobile_site');
      $cp = $this->tokenService->replace(
        $this->configFactory->get('ivw_integration.settings')->get('code_template'),
        ['entity' => $items->getEntity()],
        [
          'sanitize' => FALSE,
          'callback' => $callback,
        ]
      );
      $url = "https://$domain/$module_path/pages/amp-analytics-infonline.html";

      $elements[$delta] = [
        '#type' => 'ivw_amp',
        '#data' => [
          'vars' => [
            'st' => $st,
            'cp' => $cp,
          ],
          'requests' => [
            'url' => $url,
          ],
        ],
      ];
    }
    return $elements;
  }

}
