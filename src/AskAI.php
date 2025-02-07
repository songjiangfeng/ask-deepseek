<?php
class AskAI{

    private $ask;
    private $prompt;
    private $confg;
    private $redisclient;

   

    public function __construct($ask, $prompt, $config,$redisclient)
    {
        $this->ask = $ask;
        $this->prompt = $prompt;
        $this->confg = $config;   
        $this->redisclient = $redisclient;
       
    }
    public function ask_deepseek_siliconflow()
    {
    
        $html = '';
        $client = $this->redisclient;
        $config = $this->confg;
        $ask = $this->ask;

        $prefix = $config['prefix'];


        $key = $prefix.$ask;
    
        if (!$client->exists($key)) {
            $html = $client->get($key);
    
        } else {
    
            try {
    
                
                $content =  $this->curl_ai_api();
                $Parsedown = new Parsedown();
                $html = $Parsedown->text( $content);
            
                $client->set($key, $html);
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
    
        }
        return $html;
    }



    public function ask_deepseek()
    {

        $html = '';
        $client = $this->redisclient;
        $config = $this->confg;
        $ask = $this->ask;
        $key =$config['prefix'].$ask;

        if ($client->exists($key)) {
            $html = $client->get($key);

        } else {

            try {

                $content =  $this->curl_ai_api();
                $Parsedown = new Parsedown();
                $html = $Parsedown->text( $content);
                $client->set($key, $html);
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }

        }
        return $html;
    }


    private function curl_data_field(){
        $config = $this->confg;
        $model = $config['model'];
        $prompt = $this->prompt;
        if($config['prefix'] == 'siliconflow:'){
            $data = [
                'model' => $model,
                'messages' => array(["role"=>"user","content"=>$prompt]),
                "stream"=>False,
                "max_tokens"=> 512,
                "stop"=>["null"],
                "temperature"=> 0.7,
                "top_p"=> 0.7,
                "top_k"=> 50,
                "frequency_penalty"=> 0.5,
                "n"=> 1,
                "response_format"=> ["type"=> "text"],
            
            ];
        }elseif($config['prefix'] == 'deepseek:'){

            $data = [
            
                'model' => $model,
                'messages' => array(["role"=>"user","content"=>$prompt]),
                "stream"=>False
            ];
        }
        return $data;
        
    }
    private function curl_ai_api()
    {

        $config = $this->confg;

        $apiKey = $config['apiKey'];
        $url = $config['url'];
      
       
        $headerArray = array("Authorization: Bearer ".$apiKey."","Content-type:application/json");
       
     
        $data  = json_encode($this->curl_data_field());
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headerArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        $res  = json_decode($output, true) ;

        $content =  $res['choices'][0]['message']['content'];
        return $content;
    }
    
    public function top100()
    {

        $config = $this->confg;
        $prefix = $config['prefix'];
        $client = $this->redisclient;
        $top = $client->scan(0, [ 'MATCH' => $prefix.'*', 'COUNT' => 100]);
        return $top[1];
    }

}
