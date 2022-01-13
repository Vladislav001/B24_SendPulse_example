<?php

$content = array(
	'grant_type' => 'refresh_token',
	'client_id' => 'app.5f206899ed5521.45054396',
	'client_secret' => 'evfjntZGkozgE59egTs9qZCuElVN0MXmaqoL8MX7SO7hx80X0T',
	'refresh_token' => '62c74f5f004abf7a004b049300000001909d03d899b05db774dad83575684add7a5c23'
);
$context = stream_context_create([
	'http' => [
		'method' => 'POST',
		'content' => http_build_query($content),
	],
]);

$response = file_get_contents('https://oauth.bitrix.info/oauth/token/', false, $context);
echo $response;
$data = json_decode($response, true);
echo $data;