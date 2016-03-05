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


	}


}


