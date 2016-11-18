<?php

namespace Drupal\ivw_integration_amp\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for amp-carousel.
 *
 * By default, this element sets #theme so that the 'ivw_amp' theme hook
 * is used for rendering, and attaches the js needed for the amp-analytics
 * component.
 *
 * Properties:
 * - #url: HTTPS location of IVW script.
 * - #st: Site ID.
 * - #cp: Code.
 *
 * @RenderElement("ivw_amp")
 */
class IvwAmp extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#theme' => 'ivw_amp',
      '#url' => NULL,
      '#st' => NULL,
      '#cp' => NULL,
      '#pre_render' => array(
        array($class, 'preRenderIvwAmp'),
      ),
    );
  }

  /**
   * Pre-render callback: Attaches the amp-carousel library.
   */
  public static function preRenderIvwAmp($element) {
    $element['#attached']['library'][] = 'amp/amp.analytics';
    return $element;
  }
}
