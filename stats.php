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
    $stmt = $pdo->prepare('SELECT * FROM result ORDER BY count DESC');
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




    // ------------------------------------------------------------初回ｱｸｾｽ
    // ------------------------------武器データ取得
    $csv_file = fopen("buki-data.csv", "r");
    $buki_data_all = [];
    $row_count = -1;
    while($buki_data_all_line = fgetcsv($csv_file)){ // 行にデータがある限り繰り返す？
        array_push($buki_data_all, $buki_data_all_line);
        $row_count += 1;
    }
    fclose($csv_file);

    // 総抽選回数取得
    $rcfile = fopen('r-count.dat', 'r+b');
    flock($rcfile, LOCK_EX);
    $r_count = fgets($rcfile);
    fclose($rcfile);
    

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
        if (isset($_POST['user_command'])) {         // ボタンタグに付随するinputタグのname=""の部分　キー名自分で指定
            $command = $_POST['user_command'];       // キー名を変数に入れておく。
            echo "<script>console.log('user_command: $command');</script>";
            switch ($command) {                 // ここからボタン毎の処理分岐
                case 'logout':                 // 例えばinputタグのvalue=""の部分が trigger ならここが動作。
                    header("Location:./UserAdmin/logout.php");
                    exit();                    
                    break;
                case 'profile':
                    header("Location:./UserAdmin/profile.php");
                    exit();                    
                    break;
                case 'favorite':
                    header("Location:./Favorite/mylist.php");
                    exit();                        
                    break;
                case 'signup';
                    header("Location:./UserAdmin/signup.php");
                    exit();
                    break;
                case 'login';
                    header("Location:./UserAdmin/login.php");
                    exit();
                    break;
                case 'wtenable';
                    header("Location:./side.php");
                    exit();
                break;
            }
        }
        // POST変数の中身を削除するなどの操作は特別必要ない多分
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
            <title>統計</title>
            <!-- 240609_0255 -->
            <style>
                body {
                    background-color: #dfdfdf;
                    font-family: 'DotGothic16', sans-serif;
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
                .mawasu-button-area {
                    position: relative;
                    width: 300px;
                    height: 200px;
                    margin-top: 10px;
                    margin-left: calc((100% - (300px)) / 2);
                    background-color: #fff;
                    border-right: 3px solid #000000;
                    border-bottom: 3px solid #000000;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .user_command_button.act {    /* ボタンの見た目 親要素*/
                    background-color: #cccccc;
                    border-right: 0px;
                    border-bottom: 0px;
                    border-top: 1px solid #000000;
                    border-left: 1px solid #000000;
                }
                .mawasu-button-area button{
                    position: absolute;
                    top: 0;
                    left: 0;
                    height: 100%;
                    width: 100%;
                    /* buttonのスタイル削除 */
                    background-color: transparent;
                    border: none;
                    cursor: pointer;
                    outline: none;
                    padding: 0;
                    appearance: none;
                }
                .mawasu-button-area:active{
                    background-color: #dbdbdb;
                    border-right: 0px;
                    border-bottom: 0px;
                    border-top: 3px solid #000000;
                    border-left: 3px solid #000000;
                }
                .all {
                    width: 400px;
                    margin-left: calc((100% - 400px) / 2);
                }
                .result {
                    width: 95%;
                    height: 200px;
                    margin-left: calc((100% - (400px * 0.95)) / 2);
                    background-color: #cccccc;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .login {
                    width: 100%;
                    height: auto;
                }

                /* ログイン状態のトグル */
                /* ログイン状態蘭のボタンスタイル */
                .user_command_button {    /* ボタンの見た目 親要素*/
                    position: relative;
                    width: 100px;
                    height: 40px;
                    margin: 0;
                    background-color: #fff;
                    border-right: 1px solid #000000;
                    border-bottom: 1px solid #000000;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .user_command_button:active {    /* ボタンが押されたとき 親要素*/
                    background-color: #e53368;
                    border-right: 0px;
                    border-bottom: 0px;
                    border-top: 1px solid #000000;
                    border-left: 1px solid #000000;
                }
                .user_command_button button{     /* ボタンそのもののスタイル 子要素*/
                    position: absolute;
                    top: 0;
                    left: 0;
                    height: 100%;
                    width: 100%;
                    /* buttonのスタイル削除 */
                    background-color: transparent;
                    border: none;
                    cursor: pointer;
                    outline: none;
                    padding: 0;
                    appearance: none;
                }
                .user_command_button form {
                    position: absolute;
                    top: 0;
                    left: 0;
                    height: 100%;
                    width: 100%;
                    margin: 0;
                }
                
                /* ------------------------------視覚的に出現率を表示する */


            </style>
            <script src="https://code.jquery.com/jquery.min.js"></script>
            <script>
    $(function() {
        // $(".switch.sec1").hide(); // 予め隠しておく
        $(".switch_button.sec1").click(function() { // クリックする領域のclassをいれる。←switch_buttonとsec1の2つを付けておく
            $(".switch.sec1").toggle(100);          // 展開・折り畳みされる領域のclassを入れる。←switchとsec1の2つを付けておく
            // 現在表示されている画像とそれを囲うdiv要素取得
            // const currentImgDivId = $(".switch_button.sec1 img:visible").attr("id"); 
            const currentImageId = $(".switch_button.sec1 img:visible").attr("id");
            
            // 次に表示する画像IDを決定
            let nextImageId;
            if (currentImageId === "img2") {
            nextImageId = "img1";
            } else if (currentImageId === "img1") {
            nextImageId = "img2";
            } else {
            // 最後の画像の場合は最初の画像に戻る
            nextImageId = "img2";
            }

            // 現在表示されている画像を非表示
            $(".switch_button.sec1 img:visible").hide();

            // 次に表示する画像を表示
            $("#" + nextImageId).show();
        });

    });
    </script>
        </head>
        <body>
        <!-- ------------------------------ メイン-->
            <div class="all">
                <div class="h1">
                    <a href="./index.php"><h1 style="margin-top:0;">ブキルーレット<span style="font-size: 18px;">（splatoon3専用）</span></h1></a>
                </div>
                <hr>
                <div class="login">
                    
                    <?php
                        if(isset($_SESSION['login_with'])){
                            echo '<div style="display:flex; justify-content:space-between; margin:0 10px;" class="switch_button sec1">';

                            // アイコンと文字をまとめるdiv
                            echo        '<div style="display:flex;">';
                            echo            '<div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#2ea65a;"></div>'; // カラーアイコン
                            echo            '<div><p style="margin:0;">'.$login_with.'でログイン中</p></div>'; // 文字
                            echo        '</div>';
                            // メニューボタンだけのdiv
                            echo        '<div>';
                            echo            '<img id="img1" src="./burger-icon.png" style="width:24px; height:24px;">';
                            echo            '<img id="img2" src="./ku-icon.png"     style="width:24px; height:24px; display:none;">';
                            echo        '</div>';
                            echo '</div>';


                            echo '<div style="margin:10px; display:none; display:flex; gap:0 6px;" class="switch sec1">';

                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾛｸﾞｱｳﾄ</p>';
                            echo            '<input type="hidden" name="user_command" value="logout">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button act">';
                            echo    '<form action="index.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">統計</p>';
                            echo            '<input type="hidden" name="user_command" value="stats">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ユーザ</p>';
                            echo            '<input type="hidden" name="user_command" value="profile">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style=width:calc(40% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">お気に入りブキ</p>';
                            echo            '<input type="hidden" name="user_command" value="favorite">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo '</div>';
                        }else{
                            echo '<div style="display:flex; justify-content:space-between; margin:0 10px;" class="switch_button sec1">';

                            // アイコンと文字をまとめるdiv
                            echo        '<div style="display:flex;">';
                            echo            '<div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#0a0a0a;"></div>'; // カラーアイコン
                            echo            '<div><p style="margin:0;">ゲストユーザで利用中</p></div>';
                            echo        '</div>';
                            // メニューボタンだけのdiv
                            echo        '<div>';
                            echo            '<img id="img1" src="./burger-icon.png" style="width:24px; height:24px;">';
                            echo            '<img id="img2" src="./ku-icon.png"     style="width:24px; height:24px; display:none;">';
                            echo        '</div>';
                            echo '</div>';


                            echo '<div style="margin:10px; display:none; display:flex; gap:0 6px;" class="switch sec1">';
                            echo        '<div style=width:calc(40% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾛｸﾞｲﾝ機能説明</p>';
                            echo            '<input type="hidden" name="user_command" value="wtenable">';
                            echo            '<button type="submit" id="open-popup"></button>'; // ポップアップを開くためのid=を付ける
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button act">';
                            echo    '<form action="index.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">統計</p>';
                            echo            '<input type="hidden" name="user_command" value="stats">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';


                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾕｰｻﾞ登録</p>';
                            echo            '<input type="hidden" name="user_command" value="signup">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾛｸﾞｲﾝ</p>';
                            echo            '<input type="hidden" name="user_command" value="login">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';


                            echo '</div>';
                        }

                    ?>
                </div>
                <hr>
                <div style="width:380px; margin-left:10px;">
                    <p style="font-size:16px; text-align:center;"><?= $date; ?> 時点</p>
                    <p style="font-size:16px; text-align:center;"><a href="./graph.php" target="_brank">グラフを１画面で見る(別タブ)</a></p>

                    <div style="display:flex; justify-content:space-between; width:100%;">
                        <h2>回転数</h2>
                    </div>

                    <!-- 回転数 -->
                    <p style="margin-left:10px;">総回転数　　　　 : <span style="font-size:40px; font-weight:bold;"><?= $sum + 1473; ?></span></p>
                    <p style="margin-left:10px;">集計開始前の合計 : 1473</p>
                    <p style="margin-left:10px;">集計開始後の合計 : <?= $sum; ?></p>

                    <!-- ↓古いランキング部 -->
                    <!-- <hr> -->
                    <!-- ランキング -->
                    <!-- <div style="display:flex; justify-content:space-between; width:100%;">
                        <h2>ランキング</h2>
                    </div>
                    <div style="position:relative;">
                        <table style="width:100%;">
                        <tr style="">
                            <td style="border-top: 2px dashed black; border-bottom: 2px dashed black; text-align:center; padding:5px 0; width:35px"><strong>順位</strong></td>
                            <td style="border-top: 2px dashed black; border-bottom: 2px dashed black; text-align:center; padding:5px 0; width:265px"><strong>ブキ名</strong></td>
                            <td style="border-top: 2px dashed black; border-bottom: 2px dashed black; text-align:center; padding:5px 0;"><strong>出現回数</strong></td>
                        </tr> -->
                        <?php
                            // $mem = 1; // その時々の順位を記憶する 始めは1位からスタート
                            // for($i=0; $i<count($result); $i++){
                            //     echo '<tr>';
                            //     echo    '<td style="text-align:center; padding:0; height:30px;">';
                            //     /**
                            //      * 1回目のﾙｰﾌﾟではなく、
                            //      * 前回表示した出現回数よりも、今回表示の出現回数が小さいとき、
                            //      * 現在順位$memを1増やす
                            //      */
                            //     if($i != 0 and $result[$i]["count"] < $result[$i - 1]["count"]){
                            //         $mem ++;
                            //     }
                            //     echo    '<strong>'.$mem.'</strong><span style="font-size:80%;">位</span></td>';
                            //     echo    '<td style="text-align:center; padding:0; height:30px;">'.$result[$i]["buki_name"].'</td>';
                            //     echo    '<td style="text-align:center; padding:0; height:30px;">'.$result[$i]["count"].'</td>';
                            //     echo '</tr>';
                            //     echo '';

                            // }




                        ?>
                        <!-- </table> -->

                        <?php // 棒グラフ
                            // 位置調整用パラメータ
                            // $max_width = 376; // [px]ブキ名の部分の横幅いっぱい
                            // $max_count = $result[0]['count']; // 最大出現数　この横幅が376pxに等しい
                            // $gap_height = 32; // [px]次のブキ名部分までの上下の差
                            // $default_margin_top = 41; // カラム名の部分の高さ　オフセット
                            // for($i=0; $i<count($result); $i++){
                            //     $target_width = $max_width * ($result[$i]['count'] / $max_count); // [px]
                            //     $target_margin_top = $default_margin_top + ($gap_height * $i);
                            //     echo '<div 
                            //     style="
                            //     position:absolute;
                            //     width:'.$target_width.'px;
                            //     height:30px;
                            //     background-color:#a8adbc;
                            //     z-index:-1;
                            //     left:2px;
                            //     top:'.$target_margin_top.'px;
                            //     "></div>';
                            // }
                        ?>

                <!-- ↑ 古いランキング部 -->
                <!-- ↓ 新しいランキング部 -->

<hr>
<div style="display:flex; justify-content:space-between; width:100%;">
    <h2>ランキング</h2>
</div>
<div style="position:relative;">
    <table style="width:100%; border-collapse: collapse;"> <tr>
            <td style="border-top: 2px dashed black; border-bottom: 2px dashed black; text-align:center; padding:5px 0; width:35px"><strong>順位</strong></td>
            <td style="border-top: 2px dashed black; border-bottom: 2px dashed black; text-align:center; padding:5px 0; width:265px"><strong>ブキ名</strong></td>
            <td style="border-top: 2px dashed black; border-bottom: 2px dashed black; text-align:center; padding:5px 0;"><strong>出現回数</strong></td>
        </tr>
        <?php
            // ★ グラフの最大値（＝先頭行の出現回数）を取得
            $max_count = 0;
            if (!empty($result)) {
                $max_count = $result[0]['count'];
            }
        
            $mem = 1; // その時々の順位を記憶する 始めは1位からスタート
            for($i=0; $i<count($result); $i++){

                // ★ ここでグラフのスタイルを計算する
                $bar_percentage = ($max_count > 0) ? ($result[$i]['count'] / $max_count) * 100 : 0;
                $style = sprintf(
                    'background: linear-gradient(to right, #a8adbc %f%%, transparent %f%%);',
                    $bar_percentage,
                    $bar_percentage
                );

                // ★ 計算したスタイルを下の <tr> に適用する
                echo '<tr style="' . $style . '">';

                // ★ 順位決定ロジック (元のコードをそのまま使用)
                if($i != 0 and $result[$i]["count"] < $result[$i - 1]["count"]){
                    $mem ++;
                }

                // ★ 重要：各<td>の背景を transparent (透明) にしないと、行の背景が見えなくなる
                echo '<td style="text-align:center; padding:0; height:30px; background-color: transparent;">';
                echo    '<strong>'.$mem.'</strong><span style="font-size:80%;">位</span></td>';
                echo '<td style="text-align:center; padding:0; height:30px; background-color: transparent;">'.$result[$i]["buki_name"].'</td>';
                echo '<td style="text-align:center; padding:0; height:30px; background-color: transparent;">'.$result[$i]["count"].'</td>';
                echo '</tr>';
            }
        ?>
    </table>
</div>

                <!-- ↑ 新しいランキング部 -->


                    </div>
                </div>

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