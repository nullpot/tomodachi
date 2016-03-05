<?php
/**
 * Twitpic.php
 */
 
/**
 * Twitpic
 * @author mgng
 */
class Twitpic
{
  private $_api_url = null;
  private $_api_key = null;
 
  private $_oauth_consumer_key = null;
  private $_oauth_consumer_secret = null;
  private $_oauth_user_token = null;
  private $_oauth_user_secret = null;
  private $_oauth_verify_url = null;
 
  private $_oauth_nonce = null;
  private $_oauth_timestamp = null;
  private $_oauth_version = '1.0';
  private $_oauth_signature_method = 'HMAC-SHA1';
  private $_oauth_signature = null;

// hogehoge

  /**
   * construct
   * @param array $config
   * @return boolean
   */
  public function __construct( $config ) {
    $this->_api_url               = $config['api_url'];
    $this->_api_key               = $config['api_key'];
    $this->_oauth_consumer_key    = $config['oauth_consumer_key'];
    $this->_oauth_consumer_secret = $config['oauth_consumer_secret'];
    $this->_oauth_user_token      = $config['oauth_user_token'];
    $this->_oauth_user_secret     = $config['oauth_user_secret'];
    $this->_oauth_verify_url      = $config['oauth_verify_url'];
    $this->_oauth_nonce           = md5( microtime() . mt_rand() );
    $this->_oauth_timestamp       = time();
    return true;
  }
 
  /**
   * destruct
   * @return boolean
   */
  public function __destruct(){
    return true;
  }
 
  /**
   * create oauth signature
   * @return string
   */
  private function _createOauthSignature() {
    $method = 'GET';
    $url = rawurlencode( $this->_oauth_verify_url );
    $base = rawurlencode( implode( '&', array(
      'oauth_consumer_key='     . $this->_oauth_consumer_key,
      'oauth_nonce='            . $this->_oauth_nonce,
      'oauth_signature_method=' . $this->_oauth_signature_method,
      'oauth_timestamp='        . $this->_oauth_timestamp,
      'oauth_token='            . $this->_oauth_user_token,
      'oauth_version='          . $this->_oauth_version,
    ) ) );
    return base64_encode( hash_hmac(
//      'sha1',
      'rsa',
      "{$method}&{$url}&{$base}",
      $this->_oauth_consumer_secret . '&' . $this->_oauth_user_secret,
      true
    ));
  }
 
  /**
   * create X-Auth-Service-Provider header
   * @return string
   */
  private function _createAuthServiceProvider(){
    return 'X-Auth-Service-Provider: ' . $this->_oauth_verify_url;
  }
 
  /**
   * create X-Verify-Credentials-Authorization header
   * @return string
   */
  private function _createVerifyCredentialsAuthorization() {
    $config = array(
      'oauth_consumer_key'     => $this->_oauth_consumer_key,
      'oauth_nonce'            => $this->_oauth_nonce,
      'oauth_signature_method' => $this->_oauth_signature_method,
      'oauth_timestamp'        => $this->_oauth_timestamp,
      'oauth_token'            => $this->_oauth_user_token,
      'oauth_version'          => $this->_oauth_version,
      'oauth_signature'        => $this->_createOauthSignature(),
    );
    $buf = array( 'realm="http://api.twitter.com/"', );
    foreach( $config as $key => $value ) {
      $buf[] = $key . '="' . rawurlencode( $value ) . '"';
    } unset( $key, $value );
    return 'X-Verify-Credentials-Authorization: OAuth ' . implode( ',', $buf );
  }
 
  /**
   * create OAuth headers
   * @return array
   */
  private function _createOAuthHeaders() {
    return array(
      $this->_createAuthServiceProvider(),
      $this->_createVerifyCredentialsAuthorization(),
    );
  }
 
  /**
   * post
   * @param string $filepath image file path
   * @param string $mime image/(jpeg|gif|png)
   * @param string $message
   * @return string
   */
  public function post( $filepath, $mime, $message ) {
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 30 );
    curl_setopt( $curl, CURLOPT_HEADER, false );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $curl, CURLOPT_BINARYTRANSFER, true );
    curl_setopt( $curl, CURLOPT_HTTPHEADER, $this->_createOAuthHeaders() );
    curl_setopt( $curl, CURLOPT_URL, $this->_api_url );
    curl_setopt( $curl, CURLOPT_POST, true );
    curl_setopt( $curl, CURLOPT_POSTFIELDS, array(
      'key'     => $this->_api_key,
      'media'   => "@{$filepath};type={$mime};filename=" . basename( $filepath ),
      'message' => $message,
    ));
    $result = curl_exec( $curl );
    curl_close( $curl );
    return $result;
  }
 
}
