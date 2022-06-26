
<?php
require_once 'LZCompressor/LZString.php';
require_once 'LZCompressor/LZData.php';
require_once 'LZCompressor/LZReverseDictionary.php';
require_once 'LZCompressor/LZUtil.php';
require_once 'LZCompressor/LZContext.php';
require_once 'LZCompressor/LZUtil16.php';

use LZCompressor\LZString;

class BpjsService{

    /**
     * Request headers
     * @var array
     */
    private $headers;

    /**
     * X-cons-id header value
     * @var int
     */
    private $cons_id;

    /**
     * X-Timestamp header value
     * @var string
     */
    private $timestamp;

    /**
     * X-Signature header value
     * @var string
     */
    private $signature;

    /**
     * @var string
     */
    private $secret_key;

    /**
     * @var string
     */
    private $user_key;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var string
     */
    private $service_name;

    /**
     * @var string
     */
    public $full_url;

    /**
     * @var string
     */
    private $decrypt_key;

    public function __construct($configurations = null)
    {
        if (!$configurations) {

            // Take Directly from config file if no configuration provided from initialization
            global $v2consumerID;
            global $v2consumerPass;
            global $v2userKey;
            global $v2baseUrl;
            global $v2serviceName;

            $configurations = [
                'cons_id'		=> $v2consumerID, 
                'secret_key'	=> $v2consumerPass, 
                'user_key'		=> $v2userKey,
                'base_url'		=> rtrim($v2baseUrl, '/'), //'https://apijkn.bpjs-kesehatan.go.id',
                'service_name'	=> rtrim($v2serviceName, '/'), //'antreanrs' or 'vclaim-rest'
            ];
        }

        foreach ($configurations as $key => $val){
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }

        $fullUrl = rtrim($this->base_url.'/'.$this->service_name, '/');
        $this->full_url = $fullUrl.'/';

        //set X-Timestamp, X-Signature, and finally the headers
        $this->setTimestamp()->setSignature()->setHeaders();
    }

    protected function setHeaders()
    {
        $this->headers = [
            'X-cons-id' => $this->cons_id,
            'X-timestamp' => $this->timestamp,
            'X-signature' => $this->signature,
            'user_key' => $this->user_key
        ];
        return $this;
    }

    protected function getCurlHeaders($customHeaders = []) {
        $arr = [];

        foreach ($this->headers as $key => $value) {
            $arr[] = $key.': '.$value;
        }
        foreach ($customHeaders as $key => $value) {
            $arr[] = $key.': '.$value;
        }
        return $arr;
    }

    protected function setTimestamp()
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->timestamp = (string)$dateTime->getTimestamp();
        
        return $this;
    }

    protected function setSignature()
    {
        $signature = hash_hmac('sha256', $this->cons_id.'&'.$this->timestamp, $this->secret_key, true);
        $encodedSignature = base64_encode($signature);
        $this->signature = $encodedSignature;

        //decrypt_key
        $this->decrypt_key = $this->cons_id . $this->secret_key . $this->timestamp;

        return $this;
    }

    protected function stringDecrypt($key, $string){
        $encrypt_method = 'AES-256-CBC';

        // hash
        $key_hash = hex2bin(hash('sha256', $key));
  
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);

        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
  
        return $output;
    }

    protected function decompress($string){
        return LZString::decompressFromEncodedURIComponent($string);
    }

    protected function decryptResponse($response)
    {
        $responseVar = json_decode($response);
        if (isset($responseVar->response)) {
            $responseVar->response = json_decode($this->decompress($this->stringDecrypt($this->decrypt_key, $responseVar->response)));
        }
        
        return json_encode($responseVar);
    }

    public function get($url, $headers = [ 'Content-Type'=>'application/json' ])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->full_url.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   
        // curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getCurlHeaders($headers));

        $data = curl_exec($ch);
        curl_close($ch);

        return $this->decryptResponse($data);
    }

    public function post($url, $data, $headers = [ 'Content-Type'=>'application/json' ])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->full_url.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getCurlHeaders());

        $data = curl_exec($ch);

        if(curl_errno($ch)) {
            $resp = [
                'metaData' => [
                    'code' => 500,
                    'message' => "Curl Error : ".curl_error($ch)
                ]
            ];
            return json_encode($resp);
        } else {
            curl_close($ch);
        }
        
        return $this->decryptResponse($data);
    }

    public function put($url, $data, $headers = [ 'Content-Type'=>'application/json' ])
    {  
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->full_url.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getCurlHeaders($headers));
        $data = curl_exec($ch);

        if(curl_errno($ch)) {
            $resp = [
                'metaData' => [
                    'code' => 500,
                    'message' => "Curl Error : ".curl_error($ch)
                ]
            ];
            return json_encode($resp);
        } else {
            curl_close($ch);
        }
        
        return $this->decryptResponse($data);
    }


    public function delete($url, $data, $headers = [ 'Content-Type'=>'application/json' ])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->full_url.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getCurlHeaders($headers));
        $data = curl_exec($ch);
        

        if(curl_errno($ch)) {
            $resp = [
                'metaData' => [
                    'code' => 500,
                    'message' => "Curl Error : ".curl_error($ch)
                ]
            ];
            return json_encode($resp);
        } else {
            curl_close($ch);
        }
        
        return $this->decryptResponse($data);
    }

    public function getSignature()
    {        
        return $this->headers;
    }
}
