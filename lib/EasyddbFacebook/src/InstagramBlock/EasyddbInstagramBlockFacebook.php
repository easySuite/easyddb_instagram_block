<?php

namespace EasyddbFacebook\InstagramBlock;

use GuzzleHttp\Client;

/**
 * Core functionality for Facebook connection.
 */
class EasyddbInstagramBlockFacebook {

  public const BASE_AUTHORIZATION_URL = 'https://www.facebook.com';

  public const BASE_GRAPH_URL = 'https://graph.facebook.com';

  public const DEFAULT_GRAPH_VERSION = 'v19.0';

  public const ENDPOINT_DIALOG_AUTH = 'dialog/oauth';

  public const ENDPOINT_TOKEN = 'oauth/access_token';

  public const ENDPOINT_CLIENT = 'oauth/client_code';

  public const ENDPOINT_HASHTAG_SEARCH = 'ig_hashtag_search';

  public const ENDPOINT_MEDIA_RECENT = 'recent_media';

  public const ENDPOINT_MEDIA_TOP = 'top_media';

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
      // @TODO: It should not depend on external constants.
      // @TODO: It should not depend on external drupal functions.
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

    return self::BASE_AUTHORIZATION_URL . '/' . self::DEFAULT_GRAPH_VERSION . '/' . self::ENDPOINT_DIALOG_AUTH . '?' . http_build_query($params, '', $separator);
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
    $client = new Client(['base_uri' => self::BASE_GRAPH_URL]);
    $params = [
      'client_id' => $this->appId,
      'client_secret' => $this->appSecret,
      'code' => $code,
      'redirect_uri' => url(IG_BLOCK_BASE_SETTINGS_PATH, ['absolute' => TRUE]),
    ];
    try {
      $response = $client->post(self::ENDPOINT_TOKEN, ['form_params' => $params]);
      $contents = $response->getBody()->getContents();
      $decode = json_decode($contents);
      $this->shortLivedAccessToken = $decode->access_token;

      return $this->authLongLivedAccessToken();
    }
    catch (\Exception $e) {
      // @TODO: Should not rely on external drupal functions.
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
    $client = new Client(['base_uri' => self::BASE_GRAPH_URL]);

    try {
      $response = $client->get('/' . self::ENDPOINT_TOKEN, [
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
      // @TODO: Should not rely on external drupal functions.
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
    $client = new Client(['base_uri' => self::BASE_GRAPH_URL]);

    try {
      $response = $client->get('/' . self::ENDPOINT_CLIENT, [
        'query' => [
          'client_id' => $this->appId,
          'client_secret' => $this->appSecret,
          'redirect_uri' => url(IG_BLOCK_BASE_SETTINGS_PATH, ['absolute' => TRUE]),
          'access_token' => $access_token,
        ],
      ]);

      $contents = $response->getBody()->getContents();
      return json_decode($contents)->code;
    }
    catch (\Exception $e) {
      // @TODO: Should not rely on external drupal functions.
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
