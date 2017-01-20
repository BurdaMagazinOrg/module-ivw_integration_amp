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
 * - #data: The variables required by the analytics element.
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
      '#data' => NULL,
      '#pre_render' => array(
        array($class, 'preRenderIvwAmp'),
      ),
    );
  }

  /**
   * Pre-render callback: Attaches the amp-analytics library.
   */
  public static function preRenderIvwAmp($element) {
    $element['#attached']['library'][] = 'amp/amp.analytics';
    return $element;
  }
}
