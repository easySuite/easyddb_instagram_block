<?php

namespace EasyddbFacebook\InstagramBlock;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Basic functionality for fetching data related to Instagram.
 */
class EasyddbInstagramBlockDataRequest {

  public const REQUEST_LIMIT = 50;

  /**
   * Instagram user ID.
   *
   * @var int
   *  $userId
   */
  protected $userId;

  /**
   * Access token to use with requests.
   *
   * @var string
   *  $accessToken
   */
  protected $accessToken;

  /**
   * Initial array with Media.
   *
   * @var array
   *  $mediaData
   */
  protected $mediaData;

  /**
   * Constructs the object.
   *
   * @param int $userId
   *   Instagram User ID.
   * @param string $accessToken
   *   Access Token.
   */
  public function __construct(int $userId, string $accessToken) {
    $this->userId = $userId;
    $this->accessToken = $accessToken;
  }

  /**
   * Function that get hashtag id from hashtag name.
   *
   * @param string $hashtagName
   *   Hashtag without '#' symbol.
   *
   * @return int|void
   *   Return hashtag or catch Exception.
   */
  public function requestHashtagId(string $hashtagName) {
    $client = new Client(['base_uri' => EasyddbInstagramBlockFacebook::BASE_GRAPH_URL]);

    try {
      $response = $client->get('/' . EasyddbInstagramBlockFacebook::ENDPOINT_HASHTAG_SEARCH, [
        'query' => [
          'access_token' => $this->accessToken,
          'user_id' => $this->userId,
          'q' => $hashtagName,
        ],
      ]);

      $contents = $response->getBody()->getContents();
      $decode = json_decode($contents);
      return $decode->data[0]->id;
    }
    catch (\Exception $e) {
      // @TODO: Should not rely on external drupal functions.
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('easyddb_instagram_block', $e);
    }
  }

  /**
   * Function that get media by hashtag id.
   *
   * @param int $hashtagId
   *   Hashtag ID.
   * @param string $mediaEdge
   *   Type of media to get: recent|top.
   * @param array $fields
   *   Media fields to return, https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag/recent-media#returnable-fields.
   *
   * @return mixed|void
   *   Return list of media or catch Exception.
   */
  public function requestMedia(int $hashtagId, string $mediaEdge, array $fields = []) {
    $fields = !empty($fields) ? $fields : $this->getBasicMediaFields();
    $client = new Client(['base_uri' => EasyddbInstagramBlockFacebook::BASE_GRAPH_URL]);
    try {
      $url = implode('/', [$hashtagId, $mediaEdge]);
      $response = $client->get('/' . $url, [
        'query' => [
          'access_token' => $this->accessToken,
          'user_id' => $this->userId,
          'fields' => implode(',', $fields),
          'limit' => self::REQUEST_LIMIT,
        ],
      ]);

      $contents = $response->getBody()->getContents();
      $this->mediaData = json_decode($contents);
      return $this->mediaData;
    }
    catch (\Exception $e) {
      // @TODO: Should not rely on external drupal functions.
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('easyddb_instagram_block', $e);
    }
  }

  /**
   * Function that get basic IG Media fields for this module.
   *
   * @return array
   *   Return basic fields for IG Media.
   */
  public function getBasicMediaFields() {
    return [
      'id',
      'caption',
      'media_type',
      'media_url',
      'permalink',
    ];
  }

  /**
   * Function that return an array with images.
   *
   * @param int $count
   *   Number of images to return.
   *
   * @return array
   *   Return array with images.
   */
  public function getImages(int $count) {
    $i = 0;
    $returnImagesArray = [];

    while ($i < $count) {
      $currentImages = $this->mediaData;
      foreach ($currentImages->data as $values) {
        if ($values->media_type === 'IMAGE') {
          $i++;
          $returnImagesArray[] = $values;
        }
        if ($i === $count) {
          return $returnImagesArray;
        }
      }

      if (count($currentImages->data) < self::REQUEST_LIMIT) {
        break;
      }

      $client = new Client();
      try {
        $request = new Request('GET', $currentImages->paging->next);
        $response = $client->send($request);

        $contents = $response->getBody()->getContents();
        $this->mediaData = json_decode($contents);
      }
      catch (\Exception $e) {
        // @TODO: Should not rely on external drupal functions.
        drupal_set_message($e->getMessage(), 'error');
        watchdog_exception('easyddb_instagram_block', $e);
      }

    }

    return $returnImagesArray;
  }

}
