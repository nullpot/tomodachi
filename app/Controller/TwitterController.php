<?php


App::uses('AppController', 'Controller');
App::uses('ComponentCollection', 'Controller');

class TwitterController extends AppController
{

	public $uses = array();


	public function index()
	{
		$this->autoRender = false;


	}


	public function getuser($userid = null)
	{
		$this->autoRender = false;
		$this->autoLayout = false;
		require_once(APP.'/Vendor/twitter/twitteroauth.php');

		$TwitterOAuth = new TwitterOAuth(
			Configure::read('api.twitter.consumer_key'),
			Configure::read('api.twitter.consumer_key_secret'),
			Configure::read('api.twitter.access_token'),
			Configure::read('api.twitter.access_token_secret')
		);

		$TwitterOAuth->host = 'https://api.twitter.com/1.1/';

		// get follower
		$followers = $TwitterOAuth->get('followers/list');
		foreach ($followers->users as $user) {
			$user_id = $user->id_str;
			$user_name = $user->name;
			debug($user_id . ' : ' . $user_name);
		}

	}


	public function callback()
	{
		$this->autoRender = false;
		$this->autoLayout = false;
		debug($this->request);

	}

}


