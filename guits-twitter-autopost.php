<?php

/*
Plugin Name: Guits Twitter Autopost
Description: Adiciona uma metabox para twittar a partir da página do post.
Version: 1.0
Author: <a href="http://wordpress.omandriao.com.br">Guits</a>
*/

define('GUITS_TWITTER_AUTOPOST_PLUGIN_URL' , WP_PLUGIN_URL . '/guits-twitter-autopost/' ); // this constant uses in enqueue file and style

if (!class_exists('Guits_twitter_metabox')) {

  class Guits_twitter_metabox {

    private $option_prefix;

    function __construct() {

      $this->option_prefix = "guits_twitter_post_";

      # action para adicionar a metabox na edição dos posts
      add_action( 'add_meta_boxes', array($this, 'guits_tweet_custom_metabox') );

      # roda o callback ao salvar o post
      add_action( 'save_post', array($this, 'guits_metabox_save') );

      # hook para os campos na pg configurações
      add_filter('admin_init', array($this, 'guits_tweet_config_fields'));

    }

    /**
    * Adiciona os campos para preenchimento dos tokens na pg de configurações
    */

    public function guits_tweet_config_fields () {

      add_settings_section(
      'guits_twitter_configs', // Section ID
      'Configurações - API Twitter', // Section Title
      'guits_twitter_options_callback', // Callback
      'general' // What Page?  This makes the section show up on the General Settings Page
      );

      function guits_twitter_options_callback() { // Section Callback
          echo '<p>Preencha os campos abaixo com os valores da API do twitter. Mais informações no <a href="https://developer.twitter.com">painel developer do twitter.</a></p>';
      }

      register_setting('general', 'guits_twitter_oauth_access_token');
      register_setting('general', 'guits_twitter_oauth_access_token_secret');
      register_setting('general', 'guits_twitter_oauth_consumer_key');
      register_setting('general', 'guits_twitter_oauth_consumer_secret');

      add_settings_field(
        'guits_twitter[oauth_access_token]',
        'Twitter OAuth Access Token',
        'guits_twitter_textbox_callback',
        'general',
        'guits_twitter_configs',
        array(
          'label_for' => 'oauth_access_token',
          'oauth_access_token' // $args for callback
        )
      );

      add_settings_field(
        'guits_twitter[oauth_access_token_secret]',
        'Twitter OAuth Access Token Secret',
        'guits_twitter_textbox_callback',
        'general',
        'guits_twitter_configs',
        array(
          'label_for' => 'oauth_access_token_secret',
          'oauth_access_token_secret' // $args for callback
          )
      );

      add_settings_field(
        'guits_twitter[oauth_consumer_key]',
        'Twitter OAuth Consumer Key',
        'guits_twitter_textbox_callback',
        'general',
        'guits_twitter_configs',
        array(
          'label_for' => 'oauth_consumer_key',
          'oauth_consumer_key' // $args for callback
          )
        );

      add_settings_field(
          'guits_twitter[oauth_consumer_secret]',
          'Twitter OAuth Consumer Secret',
          'guits_twitter_textbox_callback',
          'general',
          'guits_twitter_configs',
          array(
            'label_for' => 'oauth_consumer_secret',
            'oauth_consumer_secret' // $args for callback
            )
          );

      function guits_twitter_textbox_callback($args) {
        $options = get_option('guits_twitter_' . $args[0]);

        echo '<input placeholder="' . $args[0] . '" type="text" id="'  . $args[0];
        echo '" name="guits_twitter_'  . $args[0] . '" value="';
        if($options) {
         echo  $options;
        }
        echo '"></input>';
      }

    }

    /**
    * Adds a meta box to the post editing screen
    */

    public function guits_tweet_custom_metabox() {
      # add_meta_box($id, $title, $callback, $screen);
      add_meta_box( 'guits_tweet', 'Twitter', array($this, 'guits_tweet_callback'), 'post' );
    }

    function guits_format_tweet_date($date_string): string {
      $date = new DateTime($date_string);
      $date->setTimezone(new DateTimeZone("America/Sao_Paulo"));
      return $date->format('H:i d/m/Y');
    }

    /**
    * Outputs the content of the meta box
    */

    function guits_tweet_callback( $post ): void
    {

      $ja_foi_twittado = get_option($this->option_prefix . $post->ID);

      $metabox_html = wp_nonce_field( basename( __FILE__ ), 'guits_tweet_autopost_nonce' );

      if ($ja_foi_twittado) {

        $link = $ja_foi_twittado['link'];
        $tweet = $ja_foi_twittado['tweet'];
        $tweet_id = $ja_foi_twittado['id'];
        $tweet_date = $this->guits_format_tweet_date($ja_foi_twittado['date']);

        $metabox_html .=
          "
          <p>Twittado em $tweet_date. <a href='$link'>Ver tweet</a>.</p>
          <input type='hidden' name='guits-id-tweet' value='$tweet_id'>
          <p style='padding:5px 0;'>
          <label for='guits-delete-tweet'>Remover o tweet?</label>
          <input type='checkbox' name='guits-delete-tweet' id='guits-delete-tweet'/>
          </p>
         <textarea maxlength='280' rows=1 cols=40 style='width:100%; height:6em;'
         name='guits-tweet-content' id='guits-tweet-content' readonly>$tweet</textarea>
          ";

          echo $metabox_html;
          return;

      }

      $content = get_the_excerpt($post->ID);
      $metabox_html .=
        "
        <p style='padding:5px 0;'>
        <label for='guits-post-tweet'>Twittar este post?</label>
        <input type='checkbox' name='guits-post-tweet' id='guits-post-tweet'/>
        </p>
        <textarea maxlength='280' rows=1 cols=40 style='width:100%; height:6em;'
        name='guits-tweet-content' id='guits-tweet-content'>
        $content
        </textarea>
        <p>O tweet usa por padrão a imagem em destaque do seu post. <a href='/wp-admin/options-general.php'>Gerencie as configurações do plugin</a>.</p>
        ";
        echo $metabox_html;
        return;
    }

    /**
    * Saves the custom meta box input
    */

    public function guits_metabox_save( $post_id ) {

      // Checks save status
      $is_autosave = wp_is_post_autosave( $post_id );
      $is_revision = wp_is_post_revision( $post_id );
      $is_valid_nonce = ( isset( $_POST[ 'guits_tweet_autopost_nonce' ] ) && wp_verify_nonce( $_POST[ 'guits_tweet_autopost_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';

      // Exits script depending on save status
      if ( $is_autosave || $is_revision || !$is_valid_nonce ) {
        return;
      }

      # verifica se foi optado por twittar o post

      if ( isset( $_POST[ 'guits-post-tweet' ]) && $_POST['guits-post-tweet'] == 'on' ) {

        if( isset( $_POST[ 'guits-tweet-content' ] ) ) {

          $tweet_content = sanitize_text_field( $_POST['guits-tweet-content'] );

          if (empty(trim($tweet_content))) return;

          $twitter = new Guits_twitter();

          $tweet_media_id = NULL;

          if(has_post_thumbnail($post_id)) {
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $thumbnail_path = get_attached_file( $thumbnail_id );
            $image_file = file_get_contents($thumbnail_path);
            $tweet_img = json_decode($twitter->uploadImage($image_file));
            if($tweet_img->media_id) {
              $tweet_media_id = $tweet_img->media_id;
            }
          }

          $tweet = $twitter->tweetPost($tweet_content, $tweet_media_id);
          $tweet_obj = json_decode($tweet);

          $tweet_link = "https://twitter.com/" . $tweet_obj->user->screen_name . "/status/" . $tweet_obj->id_str;
          $tweet_content = trim($tweet_obj->text);
          $tweet_date = $tweet_obj->created_at;
          $tweet_id = $tweet_obj->id;

          update_option( $this->option_prefix . $post_id, array("id" => $tweet_id, "tweet" => $tweet_content, "link" => $tweet_link, "date" => $tweet_date) );

        }

      }

      # verifica se foi optado por deletar o tweet

      if (isset( $_POST[ 'guits-delete-tweet' ]) && $_POST['guits-delete-tweet'] == 'on') {

        $tweet_id = $_POST['guits-id-tweet'];

        if ($tweet_id) {
          $twitter = new Guits_twitter();
          $tweet = $twitter->deleteTweet($tweet_id);
          $tweet_obj = json_decode($tweet);
          if ($tweet_obj->errors[0]->code == 144 || $tweet_obj->created_at) {
            # 144 significa status not found, já foi deletado por outro meio
            delete_option($this->option_prefix . $post_id);
          }
        }

      }

    }

  }

  if (is_admin()) {
    require_once("TwitterAPIExchange.php");
    require_once("Guits_twitter.php");
    $guits_twitter = new Guits_twitter_metabox;
  }

}
