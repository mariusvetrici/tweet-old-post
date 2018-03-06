<?php
/**
 * The file that defines the Tumblr Service specifics.
 *
 * A class that is used to interact with Tumblr.
 * It extends the Rop_Services_Abstract class.
 *
 * @link       https://themeisle.com/
 * @since      8.0.0
 *
 * @package    Rop
 * @subpackage Rop/includes/admin/services
 */

/**
 * Class Rop_Tumblr_Service
 *
 * @since   8.0.0
 * @link    https://themeisle.com/
 */
class Rop_Tumblr_Service extends Rop_Services_Abstract {

	/**
	 * Defines the service name in slug format.
	 *
	 * @since   8.0.0
	 * @access  protected
	 * @var     string $service_name The service name.
	 */
	protected $service_name = 'tumblr';

	/**
	 * Holds the temp data for the authenticated service.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @var     array $service The temporary data of the authenticated service.
	 */
	private $service = array();

	/**
	 * Method to inject functionality into constructor.
	 * Defines the defaults and settings for this service.
	 *
	 * @since   8.0.0
	 * @access  public
	 */
	public function init() {
		$this->display_name = 'Tumblr';
	}

	/**
	 * Method to expose desired endpoints.
	 * This should be invoked by the Factory class
	 * to register all endpoints at once.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @return mixed
	 */
	public function expose_endpoints() {
		$this->register_endpoint( 'authorize', 'authorize' );
		$this->register_endpoint( 'authenticate', 'authenticate' );
	}

	/**
	 * Method to define the api.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   string $consumer_key The Consumer Key. Default empty.
	 * @param   string $consumer_secret The Consumer Secret. Default empty.
	 * @param   string $token The Consumer Key. Default NULL.
	 * @param   string $token_secret The Consumer Secret. Default NULL.
	 * @return mixed
	 */
	public function set_api( $consumer_key = '', $consumer_secret = '', $token = null, $token_secret = null ) {
		$this->api = new \Tumblr\API\Client( $consumer_key, $consumer_secret, $token, $token_secret );
	}

	/**
	 * Method to retrieve the api object.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   string $consumer_key The Consumer Key. Default empty.
	 * @param   string $consumer_secret The Consumer Secret. Default empty.
	 * @param   string $token The Consumer Key. Default NULL.
	 * @param   string $token_secret The Consumer Secret. Default NULL.
	 * @return mixed
	 */
	public function get_api( $consumer_key = '', $consumer_secret = '', $token = null, $token_secret = null ) {
		if ( $this->api == null ) {
			$this->set_api( $consumer_key, $consumer_secret, $token, $token_secret );
		}
		return $this->api;
	}

	/**
	 * Method for authorizing the service.
	 *
	 * @codeCoverageIgnore
	 *
	 * @since   8.0.0
	 * @access  public
	 * @return mixed
	 */
	public function authorize() {
		header( 'Content-Type: text/html' );
		if ( ! session_id() ) {
			session_start();
		}

		if ( isset( $_SESSION['rop_tumblr_credentials'] ) && isset( $_SESSION['rop_tumblr_request_token'] ) ) {
			$credentials = $_SESSION['rop_tumblr_credentials'];
			$tmp_token   = $_SESSION['rop_tumblr_request_token'];

			$api            = $this->get_api( $credentials['consumer_key'], $credentials['consumer_secret'], $tmp_token['oauth_token'], $tmp_token['oauth_token_secret'] );
			$requestHandler = $api->getRequestHandler();
			$requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

			if ( ! empty( $_GET['oauth_verifier'] ) ) {
				// exchange the verifier for the keys
				$verifier    = trim( $_GET['oauth_verifier'] );
				$resp        = $requestHandler->request( 'POST', 'oauth/access_token', array('oauth_verifier' => $verifier ) );
				$out         = (string) $resp->body;
				$accessToken = array();
				parse_str( $out, $accessToken );
				unset( $_SESSION['rop_tumblr_request_token'] );
				$_SESSION['rop_tumblr_token'] = $accessToken;
			}
		}

		parent::authorize();
		// echo '<script>window.setTimeout("window.close()", 500);</script>';
	}

	/**
	 * Method for authenticate the service.
	 *
	 * @codeCoverageIgnore
	 *
	 * @since   8.0.0
	 * @access  public
	 * @return mixed
	 */
	public function authenticate() {
		if ( ! session_id() ) {
			session_start();
		}

		$this->credentials                       = $_SESSION['rop_tumblr_credentials'];
		$this->credentials['oauth_token']        = isset( $_SESSION['rop_tumblr_token']['oauth_token'] ) ? $_SESSION['rop_tumblr_token']['oauth_token'] : null;
		$this->credentials['oauth_token_secret'] = isset( $_SESSION['rop_tumblr_token']['oauth_token_secret'] ) ? $_SESSION['rop_tumblr_token']['oauth_token_secret'] : null;

		if ( isset( $_SESSION['rop_tumblr_credentials'] ) && isset( $_SESSION['rop_tumblr_token'] ) ) {
			return $this->request_and_set_user_info();
		}

		return false;
	}

	/**
	 * Helper method for requesting user info.
	 *
	 * @since   8.0.0
	 * @access  private
	 * @return bool
	 */
	protected function request_and_set_user_info() {
		$api = $this->get_api( $this->credentials['consumer_key'], $this->credentials['consumer_secret'], $this->credentials['oauth_token'], $this->credentials['oauth_token_secret'] );

		$profile = $api->getUserInfo();
		if ( isset( $profile->user->name ) ) {
			$this->service = array(
				'id'                 => $profile->user->name,
				'service'            => $this->service_name,
				'credentials'        => $this->credentials,
				'public_credentials' => array(
					'app_id' => array(
						'name'    => 'Consumer Key',
						'value'   => $this->credentials['consumer_key'],
						'private' => false,
					),
					'secret' => array(
						'name'    => 'Consumer Secret',
						'value'   => $this->credentials['consumer_secret'],
						'private' => true,
					),
				),
				'available_accounts' => $this->get_users( $profile->user->blogs ),
			);

			unset( $_SESSION['rop_tumblr_credentials'] );
			unset( $_SESSION['rop_tumblr_token'] );
			return true;
		}

		return false;
	}

	/**
	 * Method to re authenticate an user based on provided credentials.
	 * Used in DB upgrade.
	 *
	 * @param string $consumer_key          The consumer key.
	 * @param string $consumer_secret       The consumer secret.
	 * @param string $oauth_token           The oauth token.
	 * @param string $oauth_token_secret    The oauth token secret.
	 *
	 * @return bool
	 */
	public function re_authenticate( $consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret ) {
		$this->set_api( $consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret );
		$api = $this->get_api();

		$this->set_credentials(
			array(
				'consumer_key'       => $consumer_key,
				'consumer_secret'    => $consumer_secret,
				'oauth_token'        => $oauth_token,
				'oauth_token_secret' => $oauth_token_secret,
			)
		);

		try {
			$profile = $api->getUserInfo();
		} catch ( Exception $exception ) {
			$log = new Rop_Logger();
			$log->warn( 'User Info failed for Tumblr.', array( $exception ) );
		}
		if ( isset( $profile->user->name ) ) {
			$this->service = array(
				'id'                 => $profile->user->name,
				'service'            => $this->service_name,
				'credentials'        => $this->credentials,
				'public_credentials' => array(
					'app_id' => array(
						'name'    => 'Consumer Key',
						'value'   => $this->credentials['consumer_key'],
						'private' => false,
					),
					'secret' => array(
						'name'    => 'Consumer Secret',
						'value'   => $this->credentials['consumer_secret'],
						'private' => true,
					),
				),
				'available_accounts' => $this->get_users( $profile->user->blogs ),
			);

			return true;
		}

		return false;
	}

	/**
	 * Method to request a token from api.
	 *
	 * @codeCoverageIgnore
	 *
	 * @since   8.0.0
	 * @access  protected
	 * @return mixed
	 */
	public function request_api_token() {
		if ( ! session_id() ) {
			session_start();
		}

		$api            = $this->get_api();
		$requestHandler = $api->getRequestHandler();
		$requestHandler->setBaseUrl( 'https://www.tumblr.com/' );

		$resp = $requestHandler->request(
			'POST', 'oauth/request_token', array(
				'oauth_callback' => $this->get_endpoint_url( 'authorize' ),
			)
		);

		$result = (string) $resp->body;
		parse_str( $result, $request_token );

		$_SESSION['rop_tumblr_request_token'] = $request_token;

		return $request_token;
	}

	/**
	 * Method to register credentials for the service.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   array $args The credentials array.
	 */
	public function set_credentials( $args ) {
		$this->credentials = $args;
	}

	/**
	 * Returns information for the current service.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @return mixed
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Generate the sign in URL.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   array $data The data from the user.
	 * @return mixed
	 */
	public function sign_in_url( $data ) {
		$credentials = $data['credentials'];
		// @codeCoverageIgnoreStart
		if ( ! session_id() ) {
			session_start();
		}
		// @codeCoverageIgnoreEnd
		$_SESSION['rop_tumblr_credentials'] = $credentials;
		$this->set_api( $credentials['consumer_key'], $credentials['consumer_secret'] );
		$request_token = $this->request_api_token();

		$url = 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $request_token['oauth_token'];

		return $url;
	}

	/**
	 * Utility method to retrieve users from the Twitter account.
	 *
	 * @codeCoverageIgnore
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   object $data Response data from Twitter.
	 * @return array
	 */
	private function get_users( $data = null ) {
		$users = array();
		if ( $data == null ) {
			$this->set_api( $this->credentials['consumer_key'], $this->credentials['consumer_secret'], $this->credentials['oauth_token'], $this->credentials['oauth_token_secret'] );
			$api = $this->get_api();

			$profile = $api->getUserInfo();
			if ( ! isset( $profile->user->name ) ) {
				return $users;
			}
			$data = $profile->user->blogs;
		}

		foreach ( $data as $page ) {
			$img = '';
			if ( isset( $page->name ) ) {
				$img = 'https://api.tumblr.com/v2/blog/' . $page->name . '.tumblr.com/avatar';
			}

			$users[] = array(
				'id'      => $page->name,
				'name'    => $page->title,
				'account' => $page->name,
				'img'     => $img,
				'active'  => true,
			);
		}

		return $users;
	}

	/**
	 * Method for publishing with Twitter service.
	 *
	 * @since   8.0.0
	 * @access  public
	 * @param   array $post_details The post details to be published by the service.
	 * @param   array $args Optional arguments needed by the method.
	 * @return mixed
	 */
	public function share( $post_details, $args = array() ) {
		$api = $this->get_api( $this->credentials['consumer_key'], $this->credentials['consumer_secret'], $this->credentials['oauth_token'], $this->credentials['oauth_token_secret'] );

		$new_post = array(
			'type'        => 'link',
			'author'      => 'me',
			'title'       => '',
			'url'         => '',
			'description' => '',
		);

		// var_dump( $api->getBlogInfo( $args['id'] ) ); die();
		$new_post['thumbnail'] = 'http://www.gettyimages.ca/gi-resources/images/Homepage/Hero/UK/CMS_Creative_164657191_Kingfisher.jpg';
		if ( isset( $post_details['post']['post_img'] ) && $post_details['post']['post_img'] !== '' && $post_details['post']['post_img'] !== false ) {
			$new_post['thumbnail'] = $post_details['post']['post_img'];
			// $new_post['thumbnail'] = 'http://www.gettyimages.ca/gi-resources/images/Homepage/Hero/UK/CMS_Creative_164657191_Kingfisher.jpg';
		}

		$new_post['description'] = $post_details['post']['post_content'];
		if ( $post_details['post']['custom_content'] !== '' ) {
			$new_post['description'] = $post_details['post']['custom_content'];
		}

		if ( isset( $post_details['post']['post_url'] ) && $post_details['post']['post_url'] != '' ) {
			$post_format_helper = new Rop_Post_Format_Helper();
			// $link = $post_format_helper->get_short_url( 'www.themeisle.com', $post_details['post']['short_url_service'], $post_details['post']['shortner_credentials'] );
			$link            = ' ' . $post_format_helper->get_short_url( $post_details['post']['post_url'], $post_details['post']['short_url_service'], $post_details['post']['shortner_credentials'] );
			$new_post['url'] = $link;
		}

		// print_r( $new_post ); die();
		try {
			$api->createPost( $args['id'] . '.tumblr.com', $new_post );
		} catch ( Exception $exception ) {
			// Maybe log this.
			$log = new Rop_Logger();
			$log->warn( 'Posting failed for Tumblr.', array( $exception ) );
			return false;
		}

		return true;
	}
}
