
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
  3.3 User that connect to Facebook in settings should be Administrator or Developer of this app under https://developers.facebook.com/apps/[APP_ID]/roles/roles/

NOTES/WARNINGS/LIMITATION
-------------------------
1. There are limits of requests to Facebook API. Check statistic and updated number of limit for requests under https://developers.facebook.com/apps/[APP_ID]/rate-limit-details/app/
2. You can query a maximum of 30 unique hashtags within a 7 day period. (https://developers.facebook.com/docs/instagram-api/reference/ig-hashtag-search)
3. FB API Only returns public photos and videos.
4. Recent Media: Only returns media objects published within 24 hours of query execution.
