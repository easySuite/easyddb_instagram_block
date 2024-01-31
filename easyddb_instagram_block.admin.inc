<?php

/**
 * @file
 * Contains forms and pages for instagram admin pages.
 */

/**
 * Form for authenticating user with Facebook API.
 */
function easyddb_instagram_block_admin_settings($form, &$form_state) {
  $form = [];

  $access_token = variable_get('easyddb_instagram_block_access_token', '');
  $access_token_expire = variable_get('easyddb_instagram_block_access_token_expires', 0);
  $app_id = variable_get('easyddb_instagram_block_fb_app_id', '');
  $app_secret = variable_get('easyddb_instagram_block_fb_app_secret', '');
  $token_available = !empty($access_token) && ($access_token_expire > REQUEST_TIME);

  if (isset($_GET['code']) && !$token_available) {
    $facebookAuth = new EasyddbInstagramBlockFacebook($app_id, $app_secret);

    $data = $facebookAuth->authGetAccessToken($_GET['code']);
    variable_set('easyddb_instagram_block_access_token', $data->access_token);
    variable_set('easyddb_instagram_block_access_token_expires', REQUEST_TIME + $data->expires_in);
  }

  $form['easyddb_instagram_block_fb_app_id'] = [
    '#type' => 'textfield',
    '#title' => t('Facebook App ID'),
    '#description' => t('Facebook App ID from https://developers.facebook.com/apps'),
    '#default_value' => variable_get('easyddb_instagram_block_fb_app_id', ''),
    '#required' => TRUE,
  ];

  $form['easyddb_instagram_block_fb_app_secret'] = [
    '#type' => 'textfield',
    '#title' => t('Facebook App Secret'),
    '#description' => t('Facebook App Secret from https://developers.facebook.com/apps/[FB_APP_ID]/settings/basic'),
    '#default_value' => variable_get('easyddb_instagram_block_fb_app_secret', ''),
    '#required' => TRUE,
  ];

  $form['easyddb_instagram_block_ig_user_id'] = [
    '#type' => 'textfield',
    '#title' => t('Instagram User ID'),
    '#description' => t('Instagram User ID attached to the same Facebook account that own App'),
    '#default_value' => variable_get('easyddb_instagram_block_ig_user_id', ''),
    '#required' => TRUE,
  ];

  $form['easyddb_instagram_block_cache_request'] = [
    '#type' => 'select',
    '#title' => t('Cache Request Lifetime'),
    '#description' => t('Cache will be auto-removed if hashtag, number of images or type request will be changed under block configuration.'),
    '#options' => [
      '1-hour' => t('1 hour'),
      '2-hour' => t('2 hours'),
      '4-hour' => t('4 hours'),
      '8-hour' => t('8 hours'),
      '12-hour' => t('12 hours'),
      '24-hour' => t('24 hours'),
    ],
    '#default_value' => variable_get('easyddb_instagram_block_cache_request', '2-hours'),
  ];

  $form['easyddb_instagram_block_access_token'] = [
    '#type' => 'textarea',
    '#title' => t('Long-live Access Token'),
    '#description' => t('Facebook access token. Is requested and filled automatically.'),
    '#default_value' => variable_get('easyddb_instagram_block_access_token', ''),
    '#disabled' => TRUE,
  ];

  $form['easyddb_instagram_block_access_token_expires'] = [
    '#type' => 'textfield',
    '#title' => t('Token expire at (UNIX timestamp)'),
    '#description' => date('d-M-Y H:m:i', variable_get('easyddb_instagram_block_access_token_expires', 0)),
    '#default_value' => variable_get('easyddb_instagram_block_access_token_expires', ''),
    '#disabled' => TRUE,
  ];

  $form['easyddb_instagram_block_hashtag'] = [
    '#type' => 'textfield',
    '#title' => t('Instagram Hashtag'),
    '#description' => t('Instagram hashtag that will be used for searching media'),
    '#default_value' => variable_get('easyddb_instagram_block_hashtag', ''),
    '#disabled' => TRUE,
  ];

  $form['easyddb_instagram_block_hashtag_id'] = [
    '#type' => 'textfield',
    '#title' => t('Instagram Hashtag ID'),
    '#description' => t('Instagram Hashtag ID'),
    '#default_value' => variable_get('easyddb_instagram_block_hashtag_id', ''),
    '#disabled' => TRUE,
  ];

  $form['easyddb_instagram_block_oauth_redirect_uri'] = [
    '#type' => 'textfield',
    '#title' => t('OAuth Redirect URI'),
    '#description' => t('Valid OAuth redirect URI.'),
    '#default_value' => url(IG_BLOCK_BASE_SETTINGS_PATH, ['absolute' => TRUE]),
    '#disabled' => TRUE,
  ];

  if (!empty($app_id) && !empty($app_secret)) {
    if (!$token_available) {
      $fb = new EasyddbInstagramBlockFacebook($app_id, $app_secret);

      $form['login_link'] = [
        '#prefix' => '<p>' . t('You need to connect your Facebook account to fetch data.') . '</p>',
        '#type' => 'link',
        '#title' => t('Login to Facebook'),
        '#href' => url($fb->authAuthorize(), ['absolute' => TRUE]),
      ];
    }
    else {
      $form['logout'] = [
        '#type' => 'link',
        '#title' => t('Log out'),
        '#href' => url(IG_BLOCK_BASE_SETTINGS_PATH_LOGOUT, ['absolute' => TRUE]),
      ];
    }
  }

  return system_settings_form($form);
}
