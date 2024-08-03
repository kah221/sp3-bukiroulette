<?php
    // 240609_0313～240610_1002
    date_default_timezone_set('Asia/Tokyo');
    $dt = new DateTime('now');
    $now = $dt -> format("ymdHi");
    $dt_str = $dt -> format("Y年m月d日 H時i分s秒");
    
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
    
    // ------------------------------DBのお気に入りデータを取得（ログイン中のみ）
    if(isset($_SESSION['login_with'])){
        $user_id = $_SESSION['login_with'][0]; // ログインユーザのID
        $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', getenv('DB_PASSWORD')); // DB接続
        $pdo->query('SET NAMES utf8;'); // 文字化け回避
        $stmt = $pdo->prepare('SELECT buki_id FROM favorite WHERE user_id = :user_id'); // favoriteテーブルからユーザのデータを取得
        $stmt->bindParam(':user_id', $user_id); // プレースホルダにバインド（セキュリティ上必要）
        $stmt->execute(); // SQL実行
        $fav_id_all = $stmt->fetchAll();
        // 重複するデータを消す
        $fav_id = [];
        for($i=0;$i<count($fav_id_all);$i++){
            foreach($fav_id_all[$i] as $key => $value){
                if(gettype($key) == 'string'){
                    array_push($fav_id, $value);
                }
            }
        }
        // var_dump($fav_id);

        // お気に入り機能のラジオボタンのチェック　デフォルトの設定
        if(!isset($_POST['fav'])){
            $_POST['fav'] = 'lv1';
        }
    
    }

    if(isset($_POST['command'])) {
        // echo '絞り込み条件を保持する';
        $_POST['type'] = $_POST['type'];
        $_POST['sub'] = $_POST['sub'];
        $_POST['spe'] = $_POST['spe'];
        $_POST['range'] = $_POST['range'];
        $_POST['hit'] = $_POST['hit'];
        $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
        $bukis_sorted = $buki_data_all;
    }else{
        // echo '絞り込み条件がリセットされた<br>';
        $_POST['type'] = ['shooter', 'roller', 'charger', 'slosher', 'maneuver', 'shelter', 'spinner', 'fude', 'stringer', 'wiper','blaster'];
        $_POST['sub'] = ['スプラッシュボム', 'キューバンボム', 'クイックボム', 'スプリンクラー', 'スプラッシュシールド', 'タンサンボム', 'カーリングボム', 'ロボットボム', 'ジャンプビーコン', 'ポイントセンサー', 'トラップ', 'ポイズンミスト', 'ラインマーカー', 'トーピード'];
        $_POST['spe'] = ['ウルトラショット', 'グレートバリア', 'ショクワンダー', 'マルチミサイル', 'アメフラシ', 'ナイスダマ', 'ホップソナー', 'キューインキ', 'メガホンレーザー5.1ch', 'ジェットパック', 'ウルトラハンコ', 'カニタンク', 'サメライド', 'トリプルトルネード', 'エナジースタンド', 'デコイチラシ', 'テイオウイカ', 'ウルトラチャクチ', 'スミナガシート'];
        $_POST['range'] = ['短距離級', '中距離級', '長距離級'];
        $_POST['hit'] = ['1', '2', '3', '4', '5'];
        $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
        $bukis_sorted = $buki_data_all;
    }

    // ------------------------------------------------------------関数
    function doSort($bukis, $tys, $sbs, $sps, $hits, $ranges){
        // echo 'doSort()関数が呼び出された';
        $ids = [];// 当てはまる武器情報を格納

        for($i=0; $i<count($bukis); $i++){
            $flag = 0; // 初期化処理
            foreach($tys as $ty){
                if($bukis[$i][3] == $ty){
                    $flag ++;
                }
            }
            foreach($sbs as $sb){
                if($bukis[$i][1] == $sb){
                    $flag ++;
                }
            }
            foreach($sps as $sp){
                if($bukis[$i][2] == $sp){
                    $flag ++;
                }
            }
            foreach($hits as $hit){
                if(strval($bukis[$i][4]) == $hit){
                    $flag ++;
                }
            }
            foreach($ranges as $range){
                if(strval($bukis[$i][5]) == $range){
                    $flag ++;
                }
            }
            // array_push($flags, $flag); // array_push(何処に, 何を);
            if($flag == 5){
                array_push($ids, $bukis[$i]);
            }
        }

        // ------------------------------ログイン中なら、さらにお気に入り機能による絞り込みも行う
        if(isset($_SESSION['login_with'])){
            global $fav_id;
            $ids = doSortFav($ids, $fav_id, $_POST['fav']);
        }





        return $ids;
    }

    
    //240802_0655 お気に入りブキを考慮する場合の2回目のソート（doSortの中から条件付きで呼び出し）
    function doSortFav($bukis_sorted, $fav_id, $lv){
        $result = [];
        switch($lv){
            case 'lv3':
                for($i=0; $i<count($bukis_sorted); $i++){ // 既に絞り込まれているブキの数だけ回す
                    // echo $bukis_sorted[$i][6].'<br>';
                    if(in_array($bukis_sorted[$i][6], $fav_id)){ // ↑の武器IDが、お気に入りブキIDと一致したした時
                        array_push($result, $bukis_sorted[$i]); // その武器データを新たな空の配列に入れていく
                    }
                }
                break;            
            case 'lv2':
                $result = $bukis_sorted; // コピーする
                for($i=0; $i<count($bukis_sorted); $i++){
                    // echo $bukis_sorted[$i][6].'<br>';
                    if(in_array($bukis_sorted[$i][6], $fav_id)){ // 武器IDが、お気に入りブキIDと位置した時
                        array_push($result, $bukis_sorted[$i]); // その武器データをコピーした配列に追加（お気に入りブキだけ2倍存在することになる）
                    }
                }
                break;
            case 'lv1':
                $result = $bukis_sorted;
                break;
            case 'lv0':
                for($i=0; $i<count($bukis_sorted); $i++){
                    // echo $bukis_sorted[$i][6].'<br>';
                    if(!in_array($bukis_sorted[$i][6], $fav_id)){ // ↑の武器IDが、お気に入りブキIDと異なっているとき追加（lv3と逆）
                        array_push($result, $bukis_sorted[$i]);
                    }
                }
                break;
                    
        }
        // var_dump($result);
        return $result;
    }

    // commandキーに値（trigger, allnothingなど）が書き込まれたとき（制御用ボタンが押されたとき）
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['command'])) {
            $command = $_POST['command'];
    
            switch ($command) {
                case 'trigger':
                    // 絞り込み実施
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    if(count($bukis_sorted) == 0){
                        $result_none = '条件に合うブキがありません';
                    }else{


                        // 抽選部分
                        $pointer = random_int(0, count($bukis_sorted)-1);
                        // 結果表示用変数
                        $result_main = $bukis_sorted[$pointer][0];
                        $result_sub = $bukis_sorted[$pointer][1];
                        $result_spe = $bukis_sorted[$pointer][2];
                        $result_hit = $bukis_sorted[$pointer][4];
                        $result_range = $bukis_sorted[$pointer][5];
                        // 回した回数記録ファイル（r-count.dat）を開き、総抽選回数を更新
                        $rcfile = fopen('r-count.dat', 'r+b');
                        flock($rcfile, LOCK_EX);
                        $r_count = fgets($rcfile) + 1;
                        rewind($rcfile);
                        fwrite($rcfile, $r_count);
                        fclose($rcfile);
                    }
                    break;
                case 'allselect':
                    $_POST['type'] = ['shooter', 'roller', 'charger', 'slosher', 'maneuver', 'shelter', 'spinner', 'fude', 'stringer', 'wiper', 'blaster'];
                    $_POST['sub'] = ['スプラッシュボム', 'キューバンボム', 'クイックボム', 'スプリンクラー', 'スプラッシュシールド', 'タンサンボム', 'カーリングボム', 'ロボットボム', 'ジャンプビーコン', 'ポイントセンサー', 'トラップ', 'ポイズンミスト', 'ラインマーカー', 'トーピード'];
                    $_POST['spe'] = ['ウルトラショット', 'グレートバリア', 'ショクワンダー', 'マルチミサイル', 'アメフラシ', 'ナイスダマ', 'ホップソナー', 'キューインキ', 'メガホンレーザー5.1ch', 'ジェットパック', 'ウルトラハンコ', 'カニタンク', 'サメライド', 'トリプルトルネード', 'エナジースタンド', 'デコイチラシ', 'テイオウイカ', 'ウルトラチャクチ', 'スミナガシート'];
                    $_POST['range'] = ['短距離級', '中距離級', '長距離級'];
                    $_POST['hit'] = ['1', '2', '3', '4', '5'];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'allnothing':
                    $_POST['type'] = [];
                    $_POST['sub'] = [];
                    $_POST['spe'] = [];
                    $_POST['range'] = [];
                    $_POST['hit'] = [];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'sortcheck':
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'type-allselect':
                    $_POST['type'] = ['shooter', 'roller', 'charger', 'slosher', 'maneuver', 'shelter', 'spinner', 'fude', 'stringer', 'wiper', 'blaster'];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'type-allnothing':
                    $_POST['type'] = [];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'sub-allselect':
                    $_POST['sub'] = ['スプラッシュボム', 'キューバンボム', 'クイックボム', 'スプリンクラー', 'スプラッシュシールド', 'タンサンボム', 'カーリングボム', 'ロボットボム', 'ジャンプビーコン', 'ポイントセンサー', 'トラップ', 'ポイズンミスト', 'ラインマーカー', 'トーピード'];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'sub-allnothing':
                    $_POST['sub'] = [];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'spe-allselect':
                    $_POST['spe'] = ['ウルトラショット', 'グレートバリア', 'ショクワンダー', 'マルチミサイル', 'アメフラシ', 'ナイスダマ', 'ホップソナー', 'キューインキ', 'メガホンレーザー5.1ch', 'ジェットパック', 'ウルトラハンコ', 'カニタンク', 'サメライド', 'トリプルトルネード', 'エナジースタンド', 'デコイチラシ', 'テイオウイカ', 'ウルトラチャクチ', 'スミナガシート'];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'spe-allnothing':
                    $_POST['spe'] = [];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'range-allselect':
                    $_POST['range'] = ['短距離級', '中距離級', '長距離級'];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'range-allnothing':
                    $_POST['range'] = [];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'hit-allselect':
                    $_POST['hit'] = ['1', '2', '3', '4', '5'];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
                case 'hit-allnothing':
                    $_POST['hit'] = [];
                    $bukis_sorted = doSort($buki_data_all, $_POST['type'], $_POST['sub'], $_POST['spe'], $_POST['hit'], $_POST['range']);
                    break;
            }
        }
    }

    // ------------------------------------------------------------ログイン系処理
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
            <title>武器ルーレット</title>
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
            </style>
            <script src="https://code.jquery.com/jquery.min.js"></script>
            <script>
            $(function() {
                $(".switch.sec1").hide(); // 予め隠しておく
                $(".switch_button.sec1").click(function() { // クリックする領域のclassをいれる。←switch_buttonとsec1の2つを付けておく
                    $(".switch.sec1").toggle(100);          // 展開・折り畳みされる領域のclassを入れる。←switchとsec1の2つを付けておく
                    // 現在表示されている画像とそれを囲うdiv要素取得
                    // const currentImgDivId = $(".switch_button.sec1 img:visible").attr("id"); 
                    const currentImageId = $(".switch_button.sec1 img:visible").attr("id");
                    
                    // 次に表示する画像IDを決定
                    let nextImageId;
                    if (currentImageId === "img1") {
                    nextImageId = "img2";
                    } else if (currentImageId === "img2") {
                    nextImageId = "img1";
                    } else {
                    // 最後の画像の場合は最初の画像に戻る
                    nextImageId = "img1";
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
                    <a href="./index.php"><h1 style="margin-top:0;">武器ルーレット<span style="font-size: 18px;">（splatoon3専用）</span></h1></a>
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

                            echo        '<div style="width:calc(40% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ユーザ情報</p>';
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
                            echo        '<div style=width:calc(60% - 6px);" class="user_command_button">';
                            echo    '<form action="index.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾛｸﾞｲﾝで使える機能について</p>';
                            echo            '<input type="hidden" name="user_command" value="wtenable">';
                            echo            '<button type="submit" id="open-popup"></button>'; // ポップアップを開くためのid=を付ける
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
                <div class="result">
                    <div class="result-text">
                        <?php
                            if(isset($_POST['command']) and $_POST['command'] == 'trigger'){
                                if(count($bukis_sorted) == 0){
                                    echo '<p style="text-align: center; margin: 0;" font-size: 18px;>'.$result_none.'</p>';
                                }else{
                                    echo '<p style="text-align: center; margin: 0 0 4px 0; font-size: 25px;">'.$result_main.'</p>';
                                    echo '<p style="text-align: center; margin: 0 0 4px 0; font-size: 18px;">'.$result_sub.'</p>';
                                    echo '<p style="text-align: center; margin: 0 0 4px 0; font-size: 18px;">'.$result_spe.'</p>';
                                    echo '<p style="text-align: center; margin: 0 0 4px 0; font-size: 18px;">'.$result_range.'</p>';
                                    echo '<p style="text-align: center; margin: 0;" font-size: 18px;>'.$result_hit.'確</p>';
                                }
                            }else{
                                echo '<p style="text-align: center; margin: 0 0 4px 0; font-size: 20px;">下のボタンで抽選</p>';
                            }
                        ?>
                    </div>
                </div>

                <form action="index.php" method="POST">
                    <div class="mawasu-button-area">
                        <p>まわす</p>
                        <input type="hidden" name="command" value="trigger">
                        <button type="submit"></button><br>
                    </div>
                    <div>
                        <hr>
                        <h2>絞り込み
                        <button type="submit" name="command" value="allselect">全選択</button>
                        <button type="submit" name="command" value="allnothing">全解除</button>
                        <button type="submit" name="command" value="sortcheck">絞り込み更新</button>　<?php echo (count($bukis_sorted)); ?> 件
                        </h2>
                        <?php
                            if(count($bukis_sorted) > 0){
                                echo '<p style="margin: 0; font-size: 13px;">最大10件まで表示</p>';
                                shuffle($bukis_sorted);
                                // ﾛｸﾞｲﾝしていればお気に入り武器に★マークを付ける
                                if(isset($_SESSION['login_with'])){ // ﾛｸﾞｲﾝしているとき
                                    for($i=0; $i<10; $i++){
                                        if($i < count($bukis_sorted)){
                                            echo '<p style="text-align: center; margin: 0;">';
                                            if(in_array($bukis_sorted[$i][6], $fav_id)){
                                                echo '★';
                                            }
                                            echo $bukis_sorted[$i][0].'</p>';
                                        }
                                    }
                                }else{ // ログインしていないとき
                                    for($i=0; $i<10; $i++){
                                        if($i < count($bukis_sorted)){
                                            echo '<p style="text-align: center; margin: 0;">'.$bukis_sorted[$i][0].'</p>';
                                        }
                                    }
                                }
                                if(count($bukis_sorted) > 10){
                                    echo '<p style="text-align: center; margin: 0;">...</p>';
                                }    

                            }
                        ?>
                    </div>

                    <!-- ログインしているときだけ表示されるお気に入りブキ絞り込み部分 -->
                    <?php
                    if($_SESSION['login_with'][0] != ''){
                        echo '<hr>';
                        echo '<div class="fab">';
                        echo '<h2>お気に入り機能</h2>';

                        echo '<input type="radio" id="fav3" name="fav" value="lv3"';
                            if(isset($_POST['fav']) and $_POST['fav'] == 'lv3'){
                                echo 'checked=""';
                            }
                        echo '><label for="fav3"><strong>Lv.3</strong>　お気に入りブキの出現率: 100%</label><br>';

                        echo '<input type="radio" id="fav2" name="fav" value="lv2"';
                        if(isset($_POST['fav']) and $_POST['fav'] == 'lv2'){
                            echo 'checked=""';
                        }
                        echo '><label for="fav2"><strong>Lv.2</strong>　お気に入りブキの出現率: 2倍</label><br>';

                        echo '<input type="radio" id="fav1" name="fav" value="lv1"';
                        if(isset($_POST['fav']) and $_POST['fav'] == 'lv1'){
                            echo 'checked=""';
                        }
                        echo '><label for="fav1"><strong>Lv.1</strong>　お気に入りブキの出現率: 1倍(default)</label><br>';

                        echo '<input type="radio" id="fav0" name="fav" value="lv0"';
                        if(isset($_POST['fav']) and $_POST['fav'] == 'lv0'){
                            echo 'checked=""';
                        }
                        echo '><label for="fav0"><strong>Lv.0</strong>　お気に入りブキの出現率: 0%</label>';

                        echo '</div>';
                    }
                    
                    
                        
                        
                        
                        
                    
                    ?>

                    
                    
                    
                    
                    


                    <hr>
                    <div class="type">
                        <h2>ブキ種
                        <button type="submit" name="command" value="type-allselect">ブキ種全選択</button>
                        <button type="submit" name="command" value="type-allnothing">ブキ種全解除</button>
                        </h2>
                        <div>
                            <input type="checkbox" name="type[]" value="shooter" id="shooter"
                                <?php
                                    if(isset($_POST['type']) and in_array('shooter', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="shooter">シューター</label><br>
                            <input type="checkbox" name="type[]" value="roller" id="roller"
                                <?php
                                    if(isset($_POST['type']) and in_array('roller', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="roller">ローラー</label><br>
                            <input type="checkbox" name="type[]" value="charger" id="charger"
                                <?php
                                    if(isset($_POST['type']) and in_array('charger', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="charger">チャージャー</label><br>
                            <input type="checkbox" name="type[]" value="slosher" id="slosher"
                                <?php
                                    if(isset($_POST['type']) and in_array('slosher', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="slosher">スロッシャー</label><br>
                            <input type="checkbox" name="type[]" value="maneuver" id="maneuver"
                                <?php
                                    if(isset($_POST['type']) and in_array('maneuver', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="maneuver">マニューバー</label><br>
                            <input type="checkbox" name="type[]" value="shelter" id="shelter"
                                <?php
                                    if(isset($_POST['type']) and in_array('shelter', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="shelter">シェルター</label><br>
                            <input type="checkbox" name="type[]" value="spinner" id="spinner"
                                <?php
                                    if(isset($_POST['type']) and in_array('spinner', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="spinner">スピナー</label><br>
                            <input type="checkbox" name="type[]" value="fude" id="fude"
                                <?php
                                    if(isset($_POST['type']) and in_array('fude', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="fude">フデ</label><br>
                            <input type="checkbox" name="type[]" value="stringer" id="stringer"
                                <?php
                                    if(isset($_POST['type']) and in_array('stringer', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="stringer">ストリンガー</label><br>
                            <input type="checkbox" name="type[]" value="wiper" id="wiper"
                                <?php
                                    if(isset($_POST['type']) and in_array('wiper', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="wiper">ワイパー</label><br>
                            <input type="checkbox" name="type[]" value="blaster" id="blaster"
                                <?php
                                    if(isset($_POST['type']) and in_array('blaster', $_POST['type'])){echo 'checked=""';}
                                ?>
                                ><label for="blaster">ブラスター</label><br>
                        </div>
                    </div>
                    <hr>
                    <div class="sub">
                        <h2>サブ
                        <button type="submit" name="command" value="sub-allselect">サブ全選択</button>
                        <button type="submit" name="command" value="sub-allnothing">サブ全解除</button>
                        </h2>
                        <div>
                            <input type="checkbox" name="sub[]" value="スプラッシュボム" id="スプラッシュボム"
                                <?php
                                    if(isset($_POST['sub']) and in_array('スプラッシュボム', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="スプラッシュボム">スプラッシュボム</label><br>
                            <input type="checkbox" name="sub[]" value="キューバンボム" id="キューバンボム"
                                <?php
                                    if(isset($_POST['sub']) and in_array('キューバンボム', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="キューバンボム">キューバンボム</label><br>
                            <input type="checkbox" name="sub[]" value="クイックボム" id="クイックボム"
                                <?php
                                    if(isset($_POST['sub']) and in_array('クイックボム', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="クイックボム">クイックボム</label><br>
                            <input type="checkbox" name="sub[]" value="スプリンクラー" id="スプリンクラー"
                                <?php
                                    if(isset($_POST['sub']) and in_array('スプリンクラー', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="スプリンクラー">スプリンクラー</label><br>
                            <input type="checkbox" name="sub[]" value="スプラッシュシールド" id="スプラッシュシールド"
                                <?php
                                    if(isset($_POST['sub']) and in_array('スプラッシュシールド', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="スプラッシュシールド">スプラッシュシールド</label><br>
                            <input type="checkbox" name="sub[]" value="タンサンボム" id="タンサンボム"
                                <?php
                                    if(isset($_POST['sub']) and in_array('タンサンボム', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="タンサンボム">タンサンボム</label><br>
                            <input type="checkbox" name="sub[]" value="カーリングボム" id="カーリングボム"
                                <?php
                                    if(isset($_POST['sub']) and in_array('カーリングボム', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="カーリングボム">カーリングボム</label><br>
                            <input type="checkbox" name="sub[]" value="ロボットボム" id="ロボットボム"
                                <?php
                                    if(isset($_POST['sub']) and in_array('ロボットボム', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="ロボットボム">ロボットボム</label><br>
                            <input type="checkbox" name="sub[]" value="ジャンプビーコン" id="ジャンプビーコン"
                                <?php
                                    if(isset($_POST['sub']) and in_array('ジャンプビーコン', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="ジャンプビーコン">ジャンプビーコン</label><br>
                            <input type="checkbox" name="sub[]" value="ポイントセンサー" id="ポイントセンサー"
                                <?php
                                    if(isset($_POST['sub']) and in_array('ポイントセンサー', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="ポイントセンサー">ポイントセンサー</label><br>
                            <input type="checkbox" name="sub[]" value="トラップ" id="トラップ"
                                <?php
                                    if(isset($_POST['sub']) and in_array('トラップ', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="トラップ">トラップ</label><br>
                            <input type="checkbox" name="sub[]" value="ポイズンミスト" id="ポイズンミスト"
                                <?php
                                    if(isset($_POST['sub']) and in_array('ポイズンミスト', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="ポイズンミスト">ポイズンミスト</label><br>
                            <input type="checkbox" name="sub[]" value="ラインマーカー" id="ラインマーカー"
                                <?php
                                    if(isset($_POST['sub']) and in_array('ラインマーカー', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="ラインマーカー">ラインマーカー</label><br>
                            <input type="checkbox" name="sub[]" value="トーピード" id="トーピード"
                                <?php
                                    if(isset($_POST['sub']) and in_array('トーピード', $_POST['sub'])){echo 'checked=""';}
                                ?>
                            ><label for="トーピード">トーピード</label><br>
                        </div>
                    </div>
                    <hr>
                    <div class="spe">
                        <h2>スペシャル
                        <button type="submit" name="command" value="spe-allselect">スペシャル全選択</button>
                        <button type="submit" name="command" value="spe-allnothing">スペシャル全解除</button>
                        </h2>
                        <div>
                            <input type="checkbox" name="spe[]" value="ウルトラショット" id="ウルトラショット"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ウルトラショット', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ウルトラショット">ウルトラショット</label><br>
                            <input type="checkbox" name="spe[]" value="グレートバリア" id="グレートバリア"
                                <?php
                                    if(isset($_POST['spe']) and in_array('グレートバリア', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="グレートバリア">グレートバリア</label><br>
                            <input type="checkbox" name="spe[]" value="ショクワンダー" id="ショクワンダー"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ショクワンダー', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ショクワンダー">ショクワンダー</label><br>
                            <input type="checkbox" name="spe[]" value="マルチミサイル" id="マルチミサイル"
                                <?php
                                    if(isset($_POST['spe']) and in_array('マルチミサイル', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="マルチミサイル">マルチミサイル</label><br>
                            <input type="checkbox" name="spe[]" value="アメフラシ" id="アメフラシ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('アメフラシ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="アメフラシ">アメフラシ</label><br>
                            <input type="checkbox" name="spe[]" value="ナイスダマ" id="ナイスダマ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ナイスダマ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ナイスダマ">ナイスダマ</label><br>
                            <input type="checkbox" name="spe[]" value="ホップソナー" id="ホップソナー"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ホップソナー', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ホップソナー">ホップソナー</label><br>
                            <input type="checkbox" name="spe[]" value="キューインキ" id="キューインキ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('キューインキ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="キューインキ">キューインキ</label><br>
                            <input type="checkbox" name="spe[]" value="メガホンレーザー5.1ch" id="メガホンレーザー5.1ch"
                                <?php
                                    if(isset($_POST['spe']) and in_array('メガホンレーザー5.1ch', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="メガホンレーザー5.1ch">メガホンレーザー5.1ch</label><br>
                            <input type="checkbox" name="spe[]" value="ジェットパック" id="ジェットパック"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ジェットパック', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ジェットパック">ジェットパック</label><br>
                            <input type="checkbox" name="spe[]" value="ウルトラハンコ" id="ウルトラハンコ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ウルトラハンコ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ウルトラハンコ">ウルトラハンコ</label><br>
                            <input type="checkbox" name="spe[]" value="カニタンク" id="カニタンク"
                                <?php
                                    if(isset($_POST['spe']) and in_array('カニタンク', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="カニタンク">カニタンク</label><br>
                            <input type="checkbox" name="spe[]" value="サメライド" id="サメライド"
                                <?php
                                    if(isset($_POST['spe']) and in_array('サメライド', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="サメライド">サメライド</label><br>
                            <input type="checkbox" name="spe[]" value="トリプルトルネード" id="トリプルトルネード"
                                <?php
                                    if(isset($_POST['spe']) and in_array('トリプルトルネード', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="トリプルトルネード">トリプルトルネード</label><br>
                            <input type="checkbox" name="spe[]" value="エナジースタンド" id="エナジースタンド"
                                <?php
                                    if(isset($_POST['spe']) and in_array('エナジースタンド', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="エナジースタンド">エナジースタンド</label><br>
                            <input type="checkbox" name="spe[]" value="デコイチラシ" id="デコイチラシ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('デコイチラシ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="デコイチラシ">デコイチラシ</label><br>
                            <input type="checkbox" name="spe[]" value="テイオウイカ" id="テイオウイカ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('テイオウイカ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="テイオウイカ">テイオウイカ</label><br>
                            <input type="checkbox" name="spe[]" value="ウルトラチャクチ" id="ウルトラチャクチ"
                                <?php
                                    if(isset($_POST['spe']) and in_array('ウルトラチャクチ', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="ウルトラチャクチ">ウルトラチャクチ</label><br>
                            <input type="checkbox" name="spe[]" value="スミナガシート" id="スミナガシート"
                                <?php
                                    if(isset($_POST['spe']) and in_array('スミナガシート', $_POST['spe'])){echo 'checked=""';}
                                ?>
                            ><label for="スミナガシート">スミナガシート</label><br>
                        </div>
                    </div>
                    <hr>
                    <div class="range">
                        <h2>射程
                        <button type="submit" name="command" value="range-allselect">射程全選択</button>
                        <button type="submit" name="command" value="range-allnothing">射程全解除</button>
                        </h2>
                        <div>
                            <input type="checkbox" name="range[]" value="短距離級" id="短距離級"
                                <?php
                                    if(isset($_POST['range']) and in_array('短距離級', $_POST['range'])){echo 'checked=""';}
                                ?>
                                ><label for="短距離級">短距離級</label><br>
                            <input type="checkbox" name="range[]" value="中距離級" id="中距離級"
                                <?php
                                    if(isset($_POST['range']) and in_array('中距離級', $_POST['range'])){echo 'checked=""';}
                                ?>
                                ><label for="中距離級">中距離級</label><br>
                            <input type="checkbox" name="range[]" value="長距離級" id="長距離級"
                                <?php
                                    if(isset($_POST['range']) and in_array('長距離級', $_POST['range'])){echo 'checked=""';}
                                ?>
                                ><label for="長距離級">長距離級</label><br>
                        </div>
                    </div>

                    <hr>
                    <div class="hit">
                        <h2>確定数
                        <button type="submit" name="command" value="hit-allselect">確定数全選択</button>
                        <button type="submit" name="command" value="hit-allnothing">確定数全解除</button>
                        </h2>
                        <div>
                            <input type="checkbox" name="hit[]" value="1" id="h1"
                                <?php
                                    if(isset($_POST['hit']) and in_array('1', $_POST['hit'])){echo 'checked=""';}
                                ?>
                                ><label for="h1">１確</label><br>
                            <input type="checkbox" name="hit[]" value="2" id="h2"
                                <?php
                                    if(isset($_POST['hit']) and in_array('2', $_POST['hit'])){echo 'checked=""';}
                                ?>
                                ><label for="h2">２確</label><br>
                            <input type="checkbox" name="hit[]" value="3" id="h3"
                                <?php
                                    if(isset($_POST['hit']) and in_array('3', $_POST['hit'])){echo 'checked=""';}
                                ?>
                                ><label for="h3">３確</label><br>
                            <input type="checkbox" name="hit[]" value="4" id="h4"
                                <?php
                                    if(isset($_POST['hit']) and in_array('4', $_POST['hit'])){echo 'checked=""';}
                                ?>
                                ><label for="h4">４確</label><br>
                            <input type="checkbox" name="hit[]" value="5" id="h5"
                                <?php
                                    if(isset($_POST['hit']) and in_array('5', $_POST['hit'])){echo 'checked=""';}
                                ?>
                                ><label for="h5">５確</label><br>
                        </div>
                    </div>
                
                </form>



        <!-- ------------------------------ -->
                <hr>
                <?php for($i=0; $i<10; $i++){echo "<br>";} ?>
                <p style="text-align:left;">
                    <!-- - 制作期間：240609～240610<br> -->
                    <!-- - 制作時間：21時間<br> -->
                    - 制作：kah7221<br>
                    - コード：<a href="https://github.com/kah221/sp3bukiroulette">github</a><br>
                    - 総アクセス数：<?php echo $a_count;?><br>
                    - 総抽選回数：<?php echo $r_count; ?><br>
                    - 更新ログ<br>
                    　240610_0844：メインシステム完成<br>
                    　240610_0933：ブキデータ登録完了<br>
                    　240610_1000：絞り込み候補ランダム表示<br>
                    　240614_1558：誤字修正<br>
                    　240622_1924：射程, 確定数絞り込み機能追加<br>
                    　240802_0351：ログイン・お気に入りブキ機能追加<br>
                    　240802_0823：お気に入りブキ絞り込み機能追加<br>
                    　240802_0902：登録済みユーザ一覧の追加<br>
                    　240804_0604：ログイン機能説明ページの追加<br>
                </p>
                <?php for($i=0; $i<10; $i++){echo "<br>";} ?>
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