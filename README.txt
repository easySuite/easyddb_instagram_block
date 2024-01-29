
INTRODUCTION
------------

This is a very simple module that integrates with Facebook API and creates a block
containing images fetched by hashtag.

REQUIREMENTS
------------

This module depends on php curl commands to parse the information from Instagram
and thus has a dependency on php5-curl.

It also has a dependency on the drupal core block module.


INSTALLATION
------------

This module is installed like any drupal module hand has no specific
installation instructions.


INITIAL CONFIGURATION
----------------------

You can configure the settings for your Instagram block by going to the configuration page (admin/config/services/easyddb_instagram_block).
After adding required fields and save - FacebookLogin link will appear for getting first token.
Before adding configuration to this block, you will need:
1. Facebook Account connected with Instagram Business Account
2. A Facebook Page connected with Instagram (see https://smashballoon.com/doc/instagram-business-profiles/?instagram)
3. A Facebook APP registered in https://developers.facebook.com/apps
  3.1 App type: Business
  3.2 Added product 'Facebook Login for Business', in which settings is needed to specify SITE_URL/admin/config/services/easyddb_instagram_block
