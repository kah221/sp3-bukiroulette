<?php
    // 240609_0313～240610_1002
    date_default_timezone_set('Asia/Tokyo');
    $dt = new DateTime('now');
    $now = $dt -> format("ymdHi");
    $dt_str = $dt -> format("Y年m月d日 H時i分s秒");
    $date = $dt -> format("Y-m-d H:i");
    
    // ------------------------------------------------------------
    // アクセスカウンタファイルを開く
    $acfile = fopen('a-count.dat', 'r+b');
    flock($acfile, LOCK_EX);
    $a_count = fgets($acfile);


    $wflag = false;
    session_start();
    if (!isset($_SESSION['memory'])) {   // セッション変数がなかった場合（おはつor久しぶり）
        $_SESSION['memory'] = $now; // 用意
        $a_count++;   // .datファイルの中を更新！
        $wflag = true;    // timestamp書き込みのフラグを立てる
    } elseif ($_SESSION['memory'] != $now){    // 訪問して1分経過してからF5 →
        $_SESSION['memory'] = $now;
        $a_count++;
        $wflag = true;    // timestamp書き込みのフラグを立てる
    }
    // echo "変数now".$now."<br>";
    // echo "セッション変数".$_SESSION['memory']."<br>";
    // echo $a_count;

    // ------------------------------------------------------------
    // アクセス時刻ログファイルを開く
    $tmfile = fopen('a-time.dat', 'r+b');
    flock($tmfile, LOCK_EX);
    // timestampファイルを1行ずつ読み込み、最後の行を変数へ代入
    if($tmfile){
        while (!feof($tmfile)) {
            $latest = fgets($tmfile);
        }
    }
    // ここで最終訪問時刻を更新してファイルを閉じる
    if ($wflag) {
        fwrite($tmfile, "\n".$dt_str);
    }
    fclose($tmfile);

    // ------------------------------------------------------------ログイン セッションで
    // var_dump($_SESSION['login_with']);
    $login_with = $_SESSION['login_with'][1];
    echo "<script>console.log('login_with: $login_with');</script>";
    

    // ------------------------------------------------------------DBに接続するだけの関数
    function connectDB(){
        $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', getenv('DB_PASSWORD')); // DB接続
        $pdo->query('SET NAMES utf8;'); // 文字化け回避
        return $pdo;
    }

    // resultテーブルの情報を取得
    $pdo = connectDB();
    $stmt = $pdo->prepare('SELECT * FROM result');
    $stmt->execute();
    if (!$stmt->execute()) {
        echo '読み取り失敗<br>';
    }
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // var_dump($result);
    $sum = 0;
    foreach($result as $s){
        $sum += $s['count'];
    }
    $ave = $sum / count($result);


    // commandキーに値（trigger, allnothingなど）が書き込まれたとき（制御用ボタンが押されたとき）
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['command'])) {
            $command = $_POST['command'];
    
            switch ($command) {
                case 'trigger':
                    break;
                case 'allselect':
                    break;
                case 'allnothing':
                    break;
                case 'sortcheck':
                    break;

            }
        }
    }

    // ------------------------------------------------------------自身へのPOSTかつ、ボタン毎の処理分岐
    if ($_SERVER['REQUEST_METHOD'] == 'POST') { // ポスト通信があったかを判別

    }


    ?>

        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" href="../img/diamond.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=DotGothic16&display=swap" rel="stylesheet"></head>
            <title>グラフ</title>
            <!-- 240609_0255 -->
            <style>
                body {
                    background-color: #dfdfdf;
                    font-family: 'DotGothic16', sans-serif;
                    margin: 0;
                }
                h2 {
                    font-size: 20px;
                }
                p {
                    margin:0;
                }
                .h1 a {
                    text-decoration: none;
                    color: #000;
                }
                .all {
                    width: 100%;
                    height: 100%;
                    /* position: relative; */
                }
                .str {
                    width: 25%;
                    height: 25%;
                    position: absolute;
                    top: 37.5%;
                    left: 37.5%;
                    z-index: -1;
                }
                
                /* ------------------------------視覚的に出現率を表示する */


            </style>

        </head>
        <body bgcolor=#dfdfdf>
        <!-- ------------------------------ メイン-->
            <div class="all">
                
                <div class="str">
                    <p>sum: <?= $sum ?></p>
                    <p>max: <?= $result[0]['count'] ?></p>
                    <p>min: <?= $result[array_key_last($result)]['count'] ?></p>
                    <p>ave: <?= $ave ?></p>
                </div>

                    <?php // 棒グラフ
                        // 位置調整用パラメータ
                        $max_width = '100'; // [%]ブキ名の部分の横幅いっぱい
                        $max_height = '100';
                        $max_count = $result[0]['count']; // 最大出現数　この横幅が100%に等しい
                        for($i=0; $i<count($result); $i++){
                            $target_width = $max_width * ($result[$i]['count'] / $max_count); // [%]
                            $target_height = $max_height * (1 / count($result));
                            $target_margin_top = $target_height * $i;
                            echo '<a title="'.$result[$i]['buki_name'].' -> '.$result[$i]['count'].'">
                            <div 
                            style="
                            width:'.$target_width.'%;
                            height:'.$target_height.'%;
                            background-color:#a8adbc;
                            left:0px;
                            top:'.$target_margin_top.'px;
                            "></div></a>';
                        }
                    ?>
            </div>

        </body>
        
        

            <!-- ------------------------------背景色を時間帯によって変える部分 -->
        <script>
            function aaaFunction(){}
            aaaFunction(); // 初回呼び出し
            setInterval(aaaFunction, 1000); // 定期的に関数を呼び出す（例: 1秒ごと）
        </script>
        </html>
        <?php
        // ファイルを閉じる
        rewind($acfile);
        fwrite($acfile, $a_count);
        flock($acfile, LOCK_UN);
        fclose($acfile);
        // $_SESSION = array();

        $session_0 = $_SESSION['login_with'][0];
        $session_1 = $_SESSION['login_with'][1];
        echo "<script>console.log('ログイン状況： $session_0');</script>";
        echo "<script>console.log('ログイン状況： $session_1');</script>";



        ?>