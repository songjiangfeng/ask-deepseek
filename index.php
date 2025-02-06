<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>
    <?php echo $_GET['ask'] ?? 'Deepseek';?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
    <link rel="shortcut icon" href="https://chatboxai.app/icon.png">
</head>
<body class='bg-slate-100'>
<div class='mx-auto max-w-5xl shadow-md prose bg-white px-2 py-4'>
<h1 class="flex flex-row justify-between items-center my-4 h-8">
            <span>
                <?php echo $_GET['ask'] ?? 'Deepseek';?>
            </span>
            <a href="https://chatboxai.app" target="_blank">
                <img src="https://chatboxai.app/icon.png" class="w-12">
            </a>
        </h1>
<div class="leading-10 ">


    <?php

    set_time_limit(0);

    require './vendor/autoload.php';
    use Noodlehaus\Config;
    use Noodlehaus\Parser\Yaml;
    $conf = new Config('api.yaml',new Yaml);

    $deepseek = $conf->get('deepseek');
    $siliconflow = $conf->get('siliconflow');

    $toop =  top100("siliconflow:");
    if (!isset($_GET['ask'])) {
        foreach ($toop as $key) {
            $title = str_replace('siliconflow:', '', $key);

            print '<a class="text-sky-600" href="/index.php?ask='.$title.'">'.$title.'</a><br/>';

        }
    } else {

        $ask = $_GET['ask'] ?? '你好';
        $prompt = $_GET['comment'] ?? $ask;
        echo "<a class='text-sky-600' href=./index.php>返回</a>";
        $t1 = microtime(true);
        
        echo ask_deepseek($ask, $prompt,$deepseek);
        $t2 = microtime(true);
        echo '<p style="color:red">耗时'.round($t2 - $t1, 3).'秒<br>内存消耗: ' . round(memory_get_usage() / 1024 / 1024, 3).'mb<br/></p>';
    }

    ?>
      <a href="https://www.deepseek.com/ style="display: flex; align-items: center;" class="text-sky-500" target="_blank">
            <img src="https://chatboxai.app/icon.png" class="w-12 pr-2">
            <b style="font-size:30px">ASK DEEPSEEK AI</b>
        </a>
        <p><a a href="https://www.deepseek.com/" target="_blank">https://www.deepseek.com/</a></p>
    </div>
    </div>
  
</body>
</html>


      
<?php

function top100($prefix = 'deepseek:')
{

    $client = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
    ]);
    $top = $client->scan(0, [ 'MATCH' => $prefix.'*', 'COUNT' => 100]);
    return $top[1];
}

function ask_deepseek_siliconflow($ask, $prompt, $config)
{

    $html = '';
    $client = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
    ]);

    $apiKey = $config['apiKey'];
    $url = $config['url'];
    $model = $config['model'];
    $prefix = $config['prefix'];
    $headerArray = array("Authorization: Bearer ".$apiKey."","Content-type:application/json");

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

    $key = $prefix.$ask;

    if ($client->exists($key)) {
        $html = $client->get($key);

    } else {

        try {

            $data  = json_encode($data);

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
            $Parsedown = new Parsedown();
            $html = $Parsedown->text( $content);
        
            $client->set($key, $html);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

    }
    return $html;
}



function ask_deepseek($ask, $prompt, $config)
{

    $html = '';
    $client = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
    ]);

    $apiKey = $config['apiKey'];
    $url = $config['url'];
    $model = $config['model'];
    $prefix = $config['prefix'];
    $headerArray = array("Authorization: Bearer ".$apiKey."","Content-type:application/json");
    $data = [
        
        'model' => $model,
        'messages' => array(["role"=>"user","content"=>$prompt]),
        "stream"=>False
    ];

    $key = $prefix.$ask;

    if ($client->exists($key)) {
        $html = $client->get($key);

    } else {

        try {

            $data  = json_encode($data);
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
            
            $Parsedown = new Parsedown();
            $html = $Parsedown->text( $content);
            $client->set($key, $html);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

    }
    return $html;
}
