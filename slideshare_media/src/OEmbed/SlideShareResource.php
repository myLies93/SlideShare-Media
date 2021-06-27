<?php

namespace Drupal\slideshare_media\OEmbed;

use Drupal\media\OEmbed\Resource;

/**
 * Value object representing an SlideShare oEmbed resource.
 */
class SlideShareResource extends Resource {

  /**
   * Total count of SlideShare slides.
   *
   * @var int
   */
  protected $slidesCount = 0;

  /**
   * Base url of certain slide image.
   *
   * @var string
   */
  protected $slideImageBaseUrl = '';

  /**
   * Base url suffix of certain slide image.
   *
   * @var string
   */
  protected $slideImageBaseUrlSuffix = '';

  /**
   * Creates a rich resource with SlideShare specific.
   *
   * @param int $slides_count
   *   Count of slides.
   * @param string $slide_image_baseurl
   *   Base url of certain slide image.
   * @param string $slide_image_baseurl_suffix
   *   Base url suffix of certain slide image.
   * @param ... $rich_defaults
   *   List of arguments defined for oEmbed Resource class.
   *
   * @return \Drupal\slideshare_media\OEmbed\SlideShareResource
   *   OEmbed Resource object.
   */
  public static function slideShareRich(int $slides_count, string $slide_image_baseurl, string $slide_image_baseurl_suffix, ...$rich_defaults) {
    // Use default rich resource type.
    $resource = self::rich(...$rich_defaults);
    $resource->slidesCount = $slides_count;
    $resource->slideImageBaseUrl = $slide_image_baseurl;
    $resource->slideImageBaseUrlSuffix = $slide_image_baseurl_suffix;

    return $resource;
  }

  /**
   * Get image list of SlideShare presentation.
   *
   * @param bool $use_https
   *   Boolean flag that indicate the http schema version for urls generation.
   *
   * @return string[]
   *   List of raw string URLs.
   */
  public function getSlidesList(bool $use_https = TRUE): array {
    // If basic info about slides are not provided - return nothing.
    if (empty($this->slideImageBaseUrlSuffix) || empty($this->slideImageBaseUrl) || empty($this->slidesCount)) {
      return [];
    }

    $urls = [];
    $schema = $use_https ? 'https' : 'http';
    // SlideShare starts slides counting from 1.
    for ($i = 1; $i < $this->slidesCount; $i++) {
      $urls[] = "$schema:$this->slideImageBaseUrl{$i}$this->slideImageBaseUrlSuffix";
    }

    return $urls;
  }

}
