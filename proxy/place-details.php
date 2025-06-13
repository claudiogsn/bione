<?php
header('Content-Type: application/json');

$placeId = $_GET['place_id'] ?? null;

if (!$placeId) {
    echo json_encode(['error' => 'place_id is required']);
    exit;
}

$key = 'AIzaSyCR1Ah_f24oWlsWaOoZf1rqIPuTzS0nyA8'; // sua chave da API
$fields = 'url';

$url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$placeId&fields=$fields&key=$key";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
