<?php

namespace Drupal\slideshare_media\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Derivative plugin implementation for SlideShare oEmbed.
 */
class SlideShareOEmbedDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'slideshare' => [
        'id' => 'slideshare',
        'label' => $this->t('SlideShare presentation'),
        'providers' => ['SlideShare'],
      ] + $base_plugin_definition,
    ];

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
