<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>ASK Deepseek</title>
    <link href="./dist/main.css" rel="stylesheet">

</head>
<body>
<div class=" flex   justify-center h-screen bg-white">
<div class="leading-10 ">


    <?php

            set_time_limit(0);
    $t1 = microtime(true);
    require './vendor/autoload.php';


    $toop =  top100();
    if (!isset($_GET['ask'])) {
        foreach ($toop as $key) {
            $title = str_replace('deepseek:', '', $key);

            print '<a class="text-sky-600" href="/index.php?ask='.$title.'">'.$title.'</a><br/>';

        }
    } else {

        $ask = $_GET['ask'] ?? '你好';
        $prompt = $_GET['comment'] ?? $ask;
        echo "<a class='text-sky-600' href=./index.php>返回</a>";
        echo ask_deepseek($ask, $prompt);

    }

    $t2 = microtime(true);
    echo '<p style="color:red">耗时'.round($t2 - $t1, 3).'秒<br>内存消耗: ' . round(memory_get_usage() / 1024 / 1024, 3).'mb<br/></p>';
    ?>
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

    function ask_redis($key, $prefix = 'deepseek:')
    {
        $client = new Predis\Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);
        return $client->get($prefix.$key);
    }


    function ask_deepseek($ask, $prompt, $prefix = 'deepseek:')
    {

        $html = '';
        $client = new Predis\Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ]);

        $apiKey = 'your-api-key-here';
        $url = 'http://localhost:11434/api/generate';

        $model = 'deepseek-r1:1.5b';
        $headerArray = array("Content-type:application/json;charset='utf-8'","Accept:application/json");
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false
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

                $regex = '/<think>(.*?)<\/think>/s';
                $text = $res['response'];
                // 使用正则表达式匹配并提取 <think> 和 </think> 之间的内容
                $cleanText = preg_replace($regex, '', $text);
                $Parsedown = new Parsedown();
                $html = $Parsedown->text($cleanText);
                $client->set($key, $html);
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }

        }
        return $html;
    }
