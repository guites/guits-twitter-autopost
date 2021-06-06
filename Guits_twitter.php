<?php
/**
* Wrapper para a biblioteca TwitterAPIExchange
*/

class Guits_twitter {

  private $plugin_name;

  private $oauth_access_token;
  private $oauth_access_token_secret;
  private $consumer_key;
  private $consumer_secret;

  public $post_id;
  public $excerpt;
  public $tweet_length;

  function __construct() {

    $this->plugin_name = 'Guits-twitter-autopost';

    $this->oauth_access_token = get_option("guits_twitter_oauth_access_token", "");
    $this->oauth_access_token_secret = get_option("guits_twitter_oauth_access_token_secret", "");
    $this->consumer_key = get_option("guits_twitter_oauth_consumer_key", "");
    $this->consumer_secret = get_option("guits_twitter_oauth_consumer_secret", "");

  }

  public function getSettings(): array {
    $settings = array(
      'oauth_access_token' => $this->oauth_access_token,
      'oauth_access_token_secret' => $this->oauth_access_token_secret,
      'consumer_key' => $this->consumer_key,
      'consumer_secret' => $this->consumer_secret
    );
    return $settings;
    }

  public function tweetPost($content, $media_id = NULL): string {
    #tweet post
    $settings = $this->getSettings();
    $url = 'https://api.twitter.com/1.1/statuses/update.json';
    $requestMethod = 'POST';
    $postfields = array(
      'status' => $content
    );

    if ($media_id) $postfields['media_ids'] = $media_id;

    $twitter = new TwitterAPIExchange($settings);
    return $twitter->buildOauth($url, $requestMethod)->setPostfields($postfields)->performRequest();
  }

  public function deleteTweet($tweet_id): string {
    $settings = $this->getSettings();
    $postfields = array('id' => $tweet_id);
    $url = "https://api.twitter.com/1.1/statuses/destroy/$tweet_id.json";
    $requestMethod = "POST";

    $twitter = new TwitterAPIExchange($settings);
    $response =  $twitter->buildOauth($url, $requestMethod)
    ->setPostfields($postfields)
    ->performRequest();

    return $response;
  }

  public function uploadImage ($img_file): string {

    $settings = $this->getSettings();
    $url = 'https://upload.twitter.com/1.1/media/upload.json';
    $requestMethod = 'POST';
    $postfields = array(
      'media_data' => base64_encode($img_file)
    );

    $twitter = new TwitterAPIExchange($settings);
    $response = $twitter->buildOauth($url, $requestMethod)
    ->setPostfields($postfields)
    ->performRequest();

    return $response;

  }

}

