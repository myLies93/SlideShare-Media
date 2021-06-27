<?php

namespace Drupal\slideshare_media\Plugin\media\Source;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\Plugin\media\Source\OEmbed;
use Drupal\slideshare_media\OEmbed\SlideShareResource;
use GuzzleHttp\Exception\TransferException;

/**
 * Provides a media source plugin for oEmbed resources.
 *
 * @MediaSource(
 *   id = "oembed",
 *   label = @Translation("SlideShare oEmbed source"),
 *   allowed_field_types = {"string"},
 *   deriver = "Drupal\slideshare_media\Plugin\Derivative\SlideShareOEmbedDeriver",
 * )
 */
class SlideShareOEmbed extends OEmbed {

  /**
   * SlideShare oEmbed provider name.
   */
  const PROVIDER_NAME_SLIDESHARE = 'SlideShare';

  /**
   * The File entity storage object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $fileStorage = NULL;

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes(): array {
    return [
      'title' => $this->t('Resource title'),
      'html' => $this->t('The HTML representation of the resource'),
      'thumbnail_uri' => $this->t('Local URI of the thumbnail'),
      'total_slides' => $this->t('Slides list (For image field only)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    // Provide SlideShare presentation slides as an image field value.
    if ($name !== 'total_slides') {
      return parent::getMetadata($media, $name);
    }

    $resource = $this->getResource($media);
    if (!$resource instanceof SlideShareResource) {
      return parent::getMetadata($media, $name);
    }

    return $this->getSlidesAsImageFieldValue($resource);
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders(): array {
    return [static::PROVIDER_NAME_SLIDESHARE];
  }

  /**
   * Get oEmbed resource object.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity object.
   *
   * @return \Drupal\media\OEmbed\Resource|null
   *   OEmbed Resource object.
   */
  protected function getResource(MediaInterface $media): ?Resource {
    $media_url = $this->getSourceFieldValue($media);
    // The URL may be NULL if the source field is empty, in which case just
    // return NULL.
    if (empty($media_url)) {
      return NULL;
    }

    try {
      $resource_url = $this->urlResolver->getResourceUrl($media_url);
      return $this->resourceFetcher->fetchResource($resource_url);
    }
    catch (ResourceException $e) {
      $this->messenger->addError($e->getMessage());
      return NULL;
    }
  }

  /**
   * Get SlideShare presentation slides as a value for image field.
   *
   * This method will download images from SlideShare
   * and save it as an File entity.
   *
   * @param \Drupal\slideshare_media\OEmbed\SlideShareResource $resource
   *   SlideShare oEmbed resource object.
   *
   * @return array
   *   List of values for Drupal image field.
   */
  protected function getSlidesAsImageFieldValue(SlideShareResource $resource): array {
    $files = [];
    // Use media thumbnails directory, as it not really possible to retrieve
    // pointed directory from field settings.
    $directory = $this->getConfiguration()['thumbnails_directory'] ?? '';
    // Check if target directory is accessible.
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
        '@dir' => $directory,
      ]);
      return [];
    }

    foreach ($resource->getSlidesList() as $url) {
      $uri = parse_url($url, PHP_URL_PATH);
      $uri = "$directory/" . Crypt::hashBase64($url) . '.' . pathinfo($uri, PATHINFO_EXTENSION);
      $file = $this->loadAndSaveSlideFile($url, $uri);

      if (!$file instanceof FileInterface) {
        continue;
      }
      $files[] = [
        'target_id' => $file->id(),
      ];
    }

    return $files;
  }

  /**
   * Load SlideShare slide as an simple image and save it.
   *
   * @param string $url
   *   Image raw string URL.
   * @param string $uri
   *   Image raw string URI.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   File object.
   */
  protected function loadAndSaveSlideFile(string $url, string $uri): ?EntityInterface {
    // Load SlideShare image and save into file system.
    try {
      $response = $this->httpClient->get($url);
      if ($response->getStatusCode() === 200) {
        $uri = $this->fileSystem->saveData((string) $response->getBody(), $uri, FileSystemInterface::EXISTS_RENAME);
      }
    }
    catch (TransferException $e) {
      $this->logger->warning($e->getMessage());
      return NULL;
    }
    catch (FileException $e) {
      $this->logger->warning('Could not download remote image from {url}.', [
        'url' => $url,
      ]);
      return NULL;
    }

    // Save loaded file as a File entity.
    $storage = $this->getFileEntityStorage();
    if (!$storage instanceof EntityStorageInterface) {
      return NULL;
    }
    $file = $storage->create(['uri' => $uri]);
    try {
      $file->save();
    }
    catch (EntityStorageException $ex) {
      watchdog_exception('slideshare_media', $ex);
      return NULL;
    }

    return $file;
  }

  /**
   * Get file entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|null
   *   File entity storage object.
   */
  protected function getFileEntityStorage(): ?EntityStorageInterface {
    if ($this->fileStorage instanceof EntityStorageInterface) {
      return $this->fileStorage;
    }

    try {
      return $this->fileStorage = $this->entityTypeManager->getStorage('file');
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $ex) {
      watchdog_exception('slideshare_media', $ex);
    }

    return NULL;
  }

}
