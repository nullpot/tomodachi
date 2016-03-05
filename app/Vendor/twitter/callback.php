<?php
// callback.php
// twitterでの認証が終了時にこのページが呼び出される

	require_once (filter_input(INPUT_SERVER, 'DOCUMENT_ROOT').'/core/system/require.php');

	if(!isset($users))	{$users		= new users();}
	if(!isset($db))		{$db		= new dbi_connect();}
	$mysqli = $db->mysqli_connect;
	include_once (SITE_INSTALL_DIR . '/core/extension/twitter/tmhOAuth.php');
	include_once (SITE_INSTALL_DIR . '/core/extension/twitter/twitteroauth.php');

	// twitter から渡されるパラメータを保持
	$oauth_token		= $_GET['oauth_token'];
	$oauth_verifier		= trim($_GET['oauth_verifier']);
	$tmh = new tmhOauth(array(
		'consumer_key' => TW_CONSUMER_KEY,
		'consumer_secret' => TW_CONSUMER_SECRET,
		'token' => $oauth_token,
		'secret' => $oauth_verifier,
		'curl_ssl_verifypeer' => false
	));

	// ユーザトークンを取得
	$code = $tmh->user_request(array(
		'method' => 'POST',
		'url' => $tmh->url('oauth/access_token', ''),
		'params' => array(
			'oauth_verifier' => $oauth_verifier
		)
	));
	if($code != 200){
		// 再度リクエストを送信してみる(多分回数制限はした方がいい)
//		header("Location: http:".SITE_URL . '/login_twitter.php');
		exit;
//		// errormail
//		echo 'authcode = '.$code.' is error.'.'<br>';
//		$error_title	= 'twitterユーザトークンを取得に失敗';
//		$error_code		= 'error033';
//		$error_receive	= '$code = '.$code;
//		error_mail($error_title, $error_code, $error_receive);
//		header("Location: ".'/index.php');
	}

	// 取得したユーザトークンを保持
	$user_token	= $tmh->extract_params($tmh->response['response']);

	$accessToken		= $user_token['oauth_token'];
	$accessTokenSecret	= $user_token['oauth_token_secret'];
	$screen_name		= $user_token['screen_name'];
	$user_id_twitter	= $user_token['user_id'];
    $form               = $user_token['form'];

	// twitteroauthをnew
	$twObj = new TwitterOAuth(TW_CONSUMER_KEY, TW_CONSUMER_SECRET, $accessToken, $accessTokenSecret);

	// アイコンの取得
	// ユーザ情報のGETを実行
	$getimage	= $twObj->OAuthRequest("https://api.twitter.com/1.1/users/show.json?screen_name=".$screen_name,"GET",array());

	// 取得データをデコード
	$obj		= json_decode($getimage);

	// 取得データの中からプロフィール文を抽出
	$description = $obj->description;

	// 取得データの中からプロフィール画像だけを取得
	$avatar		= $obj->profile_image_url;

	// 大きいサイズ抽出に変更
	$order		= array("_normal.");
	$replace	= '_400x400.';
	$avatar		= str_replace($order, $replace, $avatar);

	// プロフィール画像のURLから画像データを取得
	$data		= file_get_contents($avatar);

	// 保存先ディレクトリ指定
	$dir		= SITE_INSTALL_DIR.'/content/profile_image/yurubo/twitter/';
	$sitedir	= SITE_URL.'/content/profile_image/yurubo/twitter/';

	// 保存する画像のファイル名を指定
	$filename	= $user_id_twitter.".jpg";

	$image_path	= $sitedir.$filename;
	// 画像データと指定したディレクトリに保存
	file_put_contents($dir.$filename,$data);


	$current_user = $users->current_login();
	if($current_user!=0)
	// 他のアカウントでログインしている場合
	{
		$has_account = $users->user_has_account($current_user);
		if($has_account['twitter']==1)
		{
			// すでにログインされているのでこのアクションが来るのはおかしい
			$hashphrase	= $users->create_hashphrase($current_user);
			setcookie('login_hash', $hashphrase['hash_phrase'], time() + LIMITED_OF_COOKIE, '/');
			//header("Location: http:".SITE_URL);
			//exit;
		}

		if($has_account['facebook']==1)
		{
			echo 'facebookでアカウントを持っている';
			// facebookでアカウントを持っている
			// データベースにtwitterアカウントがなければ、user_idに追加する
			// データベースにtwitterアカウントがあれば、FBにはくっつけない
			$query			= "	UPDATE users SET name_twitter = '$screen_name', image_twitter = '$image_path', twitter_id = '$user_id_twitter', twitter_access_token = '$accessToken', twitter_access_secret = '$accessTokenSecret', twitter_description = '$description' WHERE user_id = '$current_user'";
			$mysqli->query($query);

			$hashphrase	= $users->create_hashphrase($current_user);
			setcookie('login_hash', $hashphrase['hash_phrase'], time() + LIMITED_OF_COOKIE, '/');

			// redirectする
			//header("Location: http:".SITE_URL);
			//exit;
		}
	}

// 既存ユーザーかチェックする
	$query		= "SELECT * FROM users WHERE twitter_id = '$user_id_twitter'";
	$row_user	= $mysqli->query($query)->fetch_array(MYSQLI_ASSOC);

	if(!is_null($row_user['twitter_id'])){
		// yurubo.dbにデータがある = 既存ユーザー
		// user情報の更新
		$query			= "	UPDATE users SET name = '$screen_name', name_twitter = '$screen_name', image = '$image_path', image_twitter = '$image_path', twitter_id = '$user_id_twitter', twitter_access_token = '$accessToken', twitter_access_secret = '$accessTokenSecret', twitter_description = '$description'  WHERE twitter_id = '$user_id_twitter'";
		$current_user	= $row_user['user_id'];
		$mysqli->query($query);
		$query			= "	SELECT yurubo_description FROM users WHERE user_id = '$current_user'";
		$dbg	= $mysqli->query($query)->fetch_array(MYSQLI_ASSOC);
		$exist = strlen($dbg) == 0  ? 0 : 1;
		if($exist==0)
		{
			$query			= "	UPDATE users SET yurubo_description = '$description'  WHERE twitter_id = '$user_id_twitter'";
			$mysqli->query($query);
		}
	}else{
		// user新規登録
		$query	= "INSERT INTO users (name, name_twitter, image, image_twitter, twitter_id, twitter_access_token, twitter_access_secret, twitter_description, yurubo_description) VALUES ('$screen_name', '$screen_name', '$image_path', '$image_path', '$user_id_twitter', '$accessToken', '$accessTokenSecret', '$description', '$description')";
		$mysqli->query($query);

		// 新規user_id発行
		$query_user		= "SELECT * FROM users WHERE twitter_id = '$user_id_twitter'";
		$user_row		= $mysqli->query($query_user)->fetch_array(MYSQLI_ASSOC);
		$current_user	= $user_row['user_id'];
	}

	$hashphrase	= $users->create_hashphrase($current_user);
	setcookie('login_hash', $hashphrase['hash_phrase'], time() + LIMITED_OF_COOKIE, '/');

	// redirectする
	header("Location: http:".SITE_URL);
	exit;
