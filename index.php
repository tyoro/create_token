<?php

$debug = false;

if( !count($_POST) ){

?>
入力してください。<br/>
<form method="POST">
Consumer key:<input type="text" name="ck" value="<?php echo $_GET['ck']; ?>"/><br />
Consumer secret:<input type="text" name="cs" value="<?php echo $_GET['cs']; ?>"/><br />
Twitter id:<input type="text" name="ti"value="<?php echo $_GET['ti']; ?>"/><br />
Twitter pass:<input type="password" name="tp"/><br />
<input type="submit" value="submit"/>
</form>


<?php

}else{

include 'HTTP/OAuth/Consumer.php';
$consumer = new HTTP_OAuth_Consumer($_POST['ck'], $_POST['cs']);

$http_request = new HTTP_Request2();
$http_request->setConfig('ssl_verify_peer', false);
$consumer_request = new HTTP_OAuth_Consumer_Request;
$consumer_request->accept($http_request);
$consumer->accept($consumer_request);

$consumer->getRequestToken('https://twitter.com/oauth/request_token');

$oauth_token = $consumer->getToken();
//$consumer->getTokenSecret();

$auth_url = $consumer->getAuthorizeUrl('https://twitter.com/oauth/authorize');
if( $debug ){ print "auth_url:$auth_url\n"; }

//get
$fp = fopen("tmp", "w");
$ch = curl_init($auth_url);
curl_setopt($ch, CURLOPT_SSLVERSION, 3);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie");
curl_setopt($ch, CURLOPT_WRITEHEADER, $fp);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  //サーバ証明書検証をスキップ
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  //　　〃
$result = curl_exec($ch);
fclose($fp);
curl_close($ch);

if( $debug ){print $result; }

//authenticity_tokenをスクレイピングで取得
preg_match( '/authenticity_token.*?value="(.*?)"/',$result,$matches);
$authenticity_token = $matches[1];
if( $debug ){ print "authenticity_token:$authenticity_token\n"; }

$post = "authenticity_token=$authenticity_token&oauth_token=$oauth_token&session[username_or_email]=".$_POST['ti']."&session[password]=".$_POST['tp'];

//post
$ch = curl_init('https://twitter.com/oauth/authorize');
curl_setopt($ch, CURLOPT_SSLVERSION, 3);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie");
curl_setopt($ch, CURLOPT_COOKIEFILE, "/usr/local/sfw/create_token/tmp");
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  //サーバ証明書検証をスキップ
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  //　　〃
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
$result = curl_exec($ch);
curl_close($ch);

if( $debug ){print $result; }

preg_match( '/oauth_pin">\s*(\d{7})/',$result,$matches);
$oauth_pin = $matches[1];

print "oauth_pin:$oauth_pin\n";

if( $oauth_pin ){

	$consumer->getAccessToken('https://twitter.com/oauth/access_token', $oauth_pin );

	print "access_token:".$consumer->getToken()."\n";
	print "access_token_secret:".$consumer->getTokenSecret()."\n";

}

}
