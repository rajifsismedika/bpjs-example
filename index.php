<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/******* START BPJS VCLAIM *******/
require_once "BpjsVclaimService.php";

/******* SETUP BPJS VCLAIM VIA GLOBAL VARIABLE, Not Recommended *******/
// $v2consumerID = 'XXXXX';
// $v2consumerPass = 'XXXXXXXXXX';
// $v2userKey = 'XXXXXXXXXXXXXXXXXXX';
// $v2baseUrl = 'https://apijkn.bpjs-kesehatan.go.id';
// $v2serviceName = 'vclaim-rest/';
// $v2alamat=$v2baseUrl.'/'.$v2serviceName;
// $bpjsVclaimService = new BpjsVclaimService();

/******* SETUP BPJS VCLAIM VIA CONSTRUCTOR VARIABLE *******/
$bpjs_key = [
  'cons_id'		=> 'XXXXX', 
  'secret_key'	=> 'XXXXXXXXXX', 
  'user_key'		=> 'XXXXXXXXXXXXXXXXXXX',
  'base_url'		=> 'https://apijkn.bpjs-kesehatan.go.id',
  'service_name'	=> 'vclaim-rest'
];
$bpjsVclaimService = new BpjsVclaimService($bpjs_key);

// echo '<pre>';
// print_r($bpjsVclaimService->referensiPoli('ANA'));
// die;







/******* START BPJS ANTREAN *******/
require_once "BpjsAntreanService.php";

$antreanKey = [
  'cons_id'		=> 'XXXXXX', 
  'secret_key'	=> 'XXXXXXXXXX', 
  'user_key'		=> 'XXXXXXXXXXXXXXXXXXX',
  'base_url'		=> 'https://apijkn.bpjs-kesehatan.go.id',
  'service_name'	=> 'antreanrs'
];
$bpjsAntreanService = new BpjsAntreanService($antreanKey);
// echo '<pre>';
// echo 'asd';
// print_r($bpjsAntreanService->getListDokter());
