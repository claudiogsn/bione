<?php
$placeId = $_GET['place_id'];
$key = 'AIzaSyCR1Ah_f24oWlsWaOoZf1rqIPuTzS0nyA8';

$url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$placeId&key=$key&fields=url";
$response = file_get_contents($url);
header('Content-Type: application/json');
echo $response;

