<?php
if(!class_exists('GuzzleHttp')){
    require_once( WOO_BIGPOST_DIR . '/vendor/autoload.php' );    
}

use GuzzleHttp\Pool;
use GuzzleHttp\Promise;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;



class GuzzleMultiTransfer{

    private $api_url;
    private $api_key;
    private static $instance = null;
    private $client;

    private function __construct() {
        $settings = woobigpost_get_plugin_settings();
        $this->api_url = $settings['api_url'];

        $this->api_key = $settings['api_key'];

        if(strpos($this->api_url, 'staging') !== false){
            $this->api_key = $settings['testing_api_key'];
        }

        if($this->api_url == "https://staging.bigpost.com.au/"){
            $this->api_url = "https://stagingapiv2.bigpost.com.au";
        }

        $this->client = new Client(['timeout'=>60]);
    }

    /**
     * @return GuzzleMultiTransfer|null
     */
    public static function getInstance(){
        if(!self::$instance)
        {
            self::$instance = new GuzzleMultiTransfer();
        }

        return self::$instance;
    }

    /**
     * @param array $resources
     * @param array $data
     * @return mixed
     */
    public function post_request($resources = array(), $data= array()){
        try {
            //$start = microtime(true);
            if(!empty($resources)){
                $promises = [];
                $params = array(
                  'headers'=>$this->headers()
                );

                if(count($resources) > 1){
                    foreach ($resources as $key=>$resource) {
                        $params['json'] = $data[$key];
                        $promises[$key] = $this->client->postAsync($this->api_url.$resource,$params);
                    }

                    $res = \GuzzleHttp\Promise\all($promises)->then(function (array $responses) {
                        foreach ($responses as $key=>$response) {
                            $res[$key] = json_decode($response->getBody()->getContents());
                        }

                        return $res;

                    })->wait();
                } else {
                    $response = $this->client->request('POST', $this->api_url.$resources[0], [
                        'headers' => $this->headers(),
                        'json'    => $data[0]
                    ]);

                    $res[] = json_decode($response->getBody()->getContents());
                }

                //echo 'Using Guzzle Multi-Tranfer: Used a total of ' . (microtime(true) - $start) . ' seconds' . PHP_EOL;

                return $res;
            }

        } catch (ConnectException $e) {
            echo 'The following exception was encountered:' . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * @return array
     */
    private function headers(){
        return array(
            'Content-Type'=>'application/json; charset=utf-8',
            'Accesstoken'=>$this->api_key
        );
    }



}
