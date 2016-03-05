<?php


App::uses('AppController', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::import('Vendor', 'twitteroauth/autoload');
use Abraham\TwitterOAuth\TwitterOAuth;

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

		$twitter = new TwitterOAuth(
			Configure::read('api.twitter.consumer_key'),
			Configure::read('api.twitter.consumer_key_secret')
		);
		$request_token = $twitter->oauth(
			'oauth/request_token',
			array(
				'oauth_callback' => 'http://tomodachi.nullpot.com/twitter/callback'
			)
		);
		$url = $twitter->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
		$this->redirect($url);

	}


	public function callback()
	{
		$this->autoRender = false;
		$this->autoLayout = false;
		debug($this->request);

	}

}


