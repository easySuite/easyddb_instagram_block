<?php

/**
 * @file
 * Facebook classes to integrate with the Facebook API.
 *
 * Facebook Docs:
 * https://developers.facebook.com/docs/instagram/business-login-for-instagram
 * https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow
 * https://developers.facebook.com/docs/facebook-login/guides/access-tokens
 * https://developers.facebook.com/docs/instagram-api/guides/hashtag-search.
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/*
 * @const string production FB URL.
 */
const BASE_AUTHORIZATION_URL = 'https://www.facebook.com';
/*
 * @const string production Graph API URL.
 */
const BASE_GRAPH_URL = 'https://graph.facebook.com';
/*
 * @const string production Graph API version.
 */
const DEFAULT_GRAPH_VERSION = 'v19.0';
/*
 * @const string production path for oauth.
 */
const ENDPOINT_DIALOG_AUTH = 'dialog/oauth';
/*
 * @const access token endpoint.
 */
const ENDPOINT_TOKEN = 'oauth/access_token';
/*
 * @const access token endpoint.
 */
const ENDPOINT_CLIENT = 'oauth/client_code';
/*
 * @const access token endpoint.
 */
const ENDPOINT_HASHTAG_SEARCH = 'ig_hashtag_search';
/*
 * @const Most recently published photo & video with a specific hashtag.
 */
const ENDPOINT_MEDIA_RECENT = 'recent_media';
/*
 * @const Most popular photo & video that have been tagged with the hashtag.
 */
const ENDPOINT_MEDIA_TOP = 'top_media';
/*
 * @const Limit for requesting media.
 */
const REQUEST_LIMIT = 50;

/**
 * Core functionality for Facebook connection.
 */
class EasyddbInstagramBlockFacebook {

  /**
   * Facebook application id.
   *
   * @var int
   *  $appId Facebook application id.
   */
  protected $appId;

  /**
   * Facebook application secret.
   *
   * @var int
   *  $appSecret Facebook application secret.
   */
  protected $appSecret;

  /**
   * Facebook short lived access token.
   *
   * @var string
   *  $shortLivedAccessToken
   */
  protected $shortLivedAccessToken;

  /**
   * Facebook long lived access token.
   *
   * @var string
   *  $longLivedAccessToken
   */
  protected $longLivedAccessToken;

  /**
   * Facebook long lived access token expiration.
   *
   * @var int
   *  $expiresAt time when the access token expires.
   */
  protected $expiresAt = 0;

  /**
   * Constructor for instantiating a new object.
   *
   * @param int $appId
   *   Application ID.
   * @param int $appSecret
   *   Application Secret.
   * @param int $longLivedAccessToken
   *   Application Access Token if available.
   *
   * @return void
   */
  public function __construct($appId, $appSecret, $longLivedAccessToken = '') {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    $this->longLivedAccessToken = $longLivedAccessToken;
  }

  /**
   * AuthAuthorize function.
   *
   * @return string
   *   Return URL.
   */
  public function authAuthorize(array $params = [], array $permissions = ['instagram_basic']) {
    return $this->getAuthorizationUrl(
      url(IG_BLOCK_BASE_SETTINGS_PATH, ['absolute' => TRUE]),
      $permissions,
      $params,
    );
  }

  /**
   * Function to generate authorization URL.
   *
   * @param string $redirectUrl
   *   URL for redirecting.
   * @param array $permissions
   *   Permission for fetching data.
   * @param array $params
   *   Params for authorization URL.
   * @param string $state
   *   State.
   * @param string $separator
   *   Separator.
   *
   * @return string
   *   Return URL for authorization.
   */
  public function getAuthorizationUrl($redirectUrl, array $permissions = [], array $params = [], $state = '', $separator = '&') {
    $params += [
      'client_id' => $this->appId,
      'redirect_uri' => $redirectUrl,
      'state' => $state,
      'scope' => implode(',', $permissions),
      'response_type' => 'code',
    ];

    return BASE_AUTHORIZATION_URL . '/' . DEFAULT_GRAPH_VERSION . '/' . ENDPOINT_DIALOG_AUTH . '?' . http_build_query($params, '', $separator);
  }

  /**
   * Get Facebook access token.
   *
   * @param string $code
   *   Client code.
   *
   * @return mixed
   *   Return token or catch Exception.
   */
  public function authGetAccessToken(string $code) {
    $client = new Client(['base_uri' => BASE_GRAPH_URL]);
    $params = [
      'client_id' => $this->appId,
      'client_secret' => $this->appSecret,
      'code' => $code,
      'redirect_uri' => url(IG_BLOCK_BASE_SETTINGS_PATH, ['absolute' => TRUE]),
    ];
    try {
      $response = $client->post(ENDPOINT_TOKEN, ['form_params' => $params]);
      $contents = $response->getBody()->getContents();
      $decode = json_decode($contents);
      $this->shortLivedAccessToken = $decode->access_token;

      return $this->authLongLivedAccessToken();
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('easyddb_instagram_block', $e);
    }
  }

  /**
   * Get Long-lived access token.
   *
   * @return mixed|void
   *   Return token or catch Exception.
   */
  public function authLongLivedAccessToken() {
    $client = new Client(['base_uri' => BASE_GRAPH_URL]);

    try {
      $response = $client->get('/' . ENDPOINT_TOKEN, [
        'query' => [
          'client_id' => $this->appId,
          'client_secret' => $this->appSecret,
          'fb_exchange_token' => $this->shortLivedAccessToken,
          'grant_type' => 'fb_exchange_token',
        ],
      ]);

      $contents = $response->getBody()->getContents();
      $decode = json_decode($contents);
      $this->longLivedAccessToken = $decode->access_token;

      return $decode;
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('easyddb_instagram_block', $e);
    }
  }

  /**
   * Get client code.
   *
   * @return mixed|void
   *   Return code or catch Exception.
   */
  public function authClientCode(string $access_token) {
    $client = new Client(['base_uri' => BASE_GRAPH_URL]);

    try {
      $response = $client->get('/' . ENDPOINT_CLIENT, [
        'query' => [
          'client_id' => $this->appId,
          'client_secret' => $this->appSecret,
          'redirect_uri' => url(IG_BLOCK_BASE_SETTINGS_PATH, ['absolute' => TRUE]),
          'access_token' => $access_token,
        ],
      ]);

      $contents = $response->getBody()->getContents();
      $decode = json_decode($contents);
      return $decode->code;
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('easyddb_instagram_block', $e);
    }
  }

  /**
   * Refresh access token.
   *
   * @param string $activeAccessToken
   *   Active access token.
   *
   * @return mixed|void
   *   Return token or catch Exception.
   */
  public function authRefreshAccessToken(string $activeAccessToken) {
    $code = $this->authClientCode($activeAccessToken);
    return $this->authGetAccessToken($code);
  }

}

/**
 * Basic functionality for fetching data related to Instagram.
 */
class EasyddbInstagramBlockDataRequest {
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
    $client = new Client(['base_uri' => BASE_GRAPH_URL]);

    try {
      $response = $client->get('/' . ENDPOINT_HASHTAG_SEARCH, [
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
    $client = new Client(['base_uri' => BASE_GRAPH_URL]);
    try {
      $url = implode('/', [$hashtagId, $mediaEdge]);
      $response = $client->get('/' . $url, [
        'query' => [
          'access_token' => $this->accessToken,
          'user_id' => $this->userId,
          'fields' => implode(',', $fields),
          'limit' => REQUEST_LIMIT,
        ],
      ]);

      $contents = $response->getBody()->getContents();
      $this->mediaData = json_decode($contents);
      return $this->mediaData;
    }
    catch (\Exception $e) {
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

      if (count($currentImages->data) < REQUEST_LIMIT) {
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
        drupal_set_message($e->getMessage(), 'error');
        watchdog_exception('easyddb_instagram_block', $e);
      }

    }

    return $returnImagesArray;
  }

}
