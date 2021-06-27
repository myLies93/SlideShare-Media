<?php

namespace Drupal\slideshare_media_demo\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation for image field display as a Slick slider.
 *
 * @FieldFormatter(
 *   id = "slideshare_media_demo_slick",
 *   label = @Translation("Slick slider"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class DemoSlickSliderImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $build = parent::view($items, $langcode);
    $build['#attached']['library'][] = 'slideshare_media_demo/demo_slider';
    $build['#attributes']['class'][] = 'demo-slick-slider';
    return $build;
  }

}
