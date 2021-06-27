<?php

namespace Drupal\slideshare_media\OEmbed;

use Drupal\media\OEmbed\ResourceFetcher;
use Drupal\slideshare_media\Plugin\media\Source\SlideShareOEmbed;

/**
 * OEmbed resource Fetcher decoration for SlideShare resource mapping specific.
 */
class SlideShareResourceFetcher extends ResourceFetcher {

  /**
   * {@inheritdoc}
   */
  public function createResource(array $data, $url) {
    $provider = $data['provider-name'] ?? NULL;
    // Check directly for provider name - the SlideShare service provide
    // different property names, so in order to get it work
    // with core media module, it need to manually point
    // a correct property names.
    if ($provider !== SlideShareOEmbed::PROVIDER_NAME_SLIDESHARE) {
      return parent::createResource($data, $url);
    }
    // Just for code-writing example - I do not include extra data like
    // resource authorship or cache.
    $defaults[] = $data['html'] ?? '';
    $defaults[] = $data['width'] ?? '';
    $defaults[] = $data['height'] ?? '';
    $defaults[] = $this->providers->get($provider);
    $defaults[] = $data['title'] ?? '';
    $defaults[] = NULL;
    $defaults[] = NULL;
    $defaults[] = NULL;
    $defaults[] = $data['thumbnail-url'] ?? '';
    $defaults[] = $data['thumbnail-width'] ?? '';
    $defaults[] = $data['thumbnail-height'] ?? '';

    $slides_count = $data['total-slides'] ?? 0;
    $slide_image_baseurl = $data['slide-image-baseurl'] ?? '';
    $slide_image_baseurl_suffix = $data['slide-image-baseurl-suffix'] ?? '';

    return SlideShareResource::slideShareRich((int) $slides_count, $slide_image_baseurl, $slide_image_baseurl_suffix, ...$defaults);
  }

}
