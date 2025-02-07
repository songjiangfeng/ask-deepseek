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
    $t1 = microtime(true);
    require './vendor/autoload.php';
    include_once './src/AskAI.php';
    use Noodlehaus\Config;
    use Noodlehaus\Parser\Yaml;
    $conf = new Config('api.yaml',new Yaml);

    $deepseek = $conf->get('deepseek');
    $siliconflow = $conf->get('siliconflow');

    $redisclient = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
    ]);
    
    $ask = $_GET['ask'] ?? '你好';
    $prompt = $_GET['comment'] ?? $ask;
    $deepseek_ai = new AskAI($ask,$prompt,$deepseek,$redisclient);

  
    if (!isset($_GET['ask'])) {
        foreach ($deepseek_ai->top100() as $key) {
            $title = str_replace('deepseek:', '', $key);
            print '<a class="text-sky-600" href="/index.php?ask='.$title.'">'.$title.'</a><br/>';

        }
    } else {

        echo "<a class='text-sky-600' href=./index.php>返回</a>";
        echo $deepseek_ai->ask_deepseek();

    }
    $t2 = microtime(true);
    echo '<p style="color:red">耗时'.round($t2 - $t1, 3).'秒<br>内存消耗: ' . round(memory_get_usage() / 1024 / 1024, 3).'mb<br/></p>';
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

      

