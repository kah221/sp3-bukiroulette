<?php
// 自身のお気に入り武器一覧が表示される

// 流れ

// ------------------------------①ログイン状況を確認
session_start();
if($_SESSION['login_with'] != []){ // ログインできているときのみ動く
    $user_id = $_SESSION['login_with'][0];
    $user_name = $_SESSION['login_with'][1];
}else{
    header("Location:../index.php");
    exit();
}

// DBへ接続する
function connectDB(){
    $password = getenv('DB_PASSWORD');
    try {
        $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', $password);
    } catch (PDOException $e) {echo "DB接続失敗<br>: " . $e->getMessage();}
    return $pdo;
}

// ------------------------------③お気に入り武器データを取得する
/**
 * favoriteテーブルから登録されている武器IDを取得する
 * @param {object} $pdo - DBのPDO
 * @param {string} $user_id - ログインユーザのID
 * @return {} - ログインユーザのお気に入りブキIDの1次元配列[10, 12, 20, 30]
 */
function selectFavorites($pdo, $user_id){
    $pdo->query('SET NAMES utf8;'); // 文字化け回避
    $stmt = $pdo->prepare('SELECT buki_id FROM favorite WHERE user_id = :user_id'); // favoriteテーブルからユーザのデータを取得
    $stmt->bindParam(':user_id', $user_id); // プレースホルダにバインド（セキュリティ上必要）
    $stmt->execute(); // SQL実行
    $db_favorites_buki_id_all = $stmt->fetchAll();
    // 重複するデータを消す
    $db_favorites_buki_id = [];
    for($i=0;$i<count($db_favorites_buki_id_all);$i++){
        foreach($db_favorites_buki_id_all[$i] as $key => $value){
            if(gettype($key) == 'string'){
                array_push($db_favorites_buki_id, $value);
            }
        }
    }        
    return $db_favorites_buki_id;
}
// 最初の処理
$pdo = connectDB();
$db_favorites_buki_id = selectFavorites($pdo, $user_id);


// ------------------------------保存ボタンで動かす関数
function updateDB($pdo, $user_id, $checked_id){
    $msg = [];
    echo '<br>';
    var_dump($checked_id);

    // 一度既に登録されているお気に入り武器を消す
    $pdo->query('SET NAMES utf8;'); // 文字化け回避
    $stmt = $pdo->prepare('DELETE FROM favorite WHERE user_id = :user_id'); // favoriteテーブルからユーザのデータを取得
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute(); // SQL実行
    if (!$stmt->execute()) {
        array_push($msg, 'DBリセットに失敗しました');
    }

    // // チェックが付けられたブキを登録する
    try{
        foreach($checked_id as $id){
            $pdo->query('SET NAMES utf8;'); // 文字化け回避
            $stmt = $pdo->prepare('INSERT INTO favorite (user_id, buki_id) VALUES (:user_id, :buki_id)');
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':buki_id', $id);
            $stmt->execute(); // SQLを実行    
        }
        header("Location:./mylist.php");
        exit();
    }catch(e){
        array_push($msg, 'お気に入りブキの登録に失敗しました');
    }    
    return $msg;
}




// ------------------------------④武器データ取得 CSVから
$csv_file = fopen("../buki-data.csv", "r");
$buki_data_all = [];
$row_count = -1;
while($buki_data_all_line = fgetcsv($csv_file)){ // 行にデータがある限り繰り返す？
    array_push($buki_data_all, $buki_data_all_line);
    $row_count += 1;
}
fclose($csv_file);
// var_dump($buki_data_all);
// ■■■$buki_data_all[★][]の(★-1)がfavoriteテーブルの buki_id に対応  buki_id + 1 = ★  ■■■

// ------------------------------------------------------------
// 比較するもの↓
// $db_favorites_buki_id    ①連想配列
// $buki_data_all   ②2次元配列
// 武器単体の情報は
// $db_favorites_buki_id[]
// $buki_data_all[]

// $favorites 二次元配列。最終的に表示に使う。


// ------------------------------favoriteに登録されているぶきIDから、CSVデータを持ってくる。
$favorites = [];
foreach($db_favorites_buki_id as $buki_id){
    array_push($favorites, $buki_data_all[$buki_id]);
}


// ------------------------------------------------------------ログイン系処理
// ------------------------------------------------------------自身へのPOSTかつ、ボタン毎の処理分岐
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // ポスト通信があったかを判別
    if (isset($_POST['user_command'])) {         // ボタンタグに付随するinputタグのname=""の部分　キー名自分で指定
        $command = $_POST['user_command'];       // キー名を変数に入れておく。
        echo "<script>console.log('user_command: $command');</script>";
        switch ($command) {                 // ここからボタン毎の処理分岐
            // case 'cancel':                 // 例えばinputタグのvalue=""の部分が trigger ならここが動作。
            //     header("Location:./mylist.php");
            //     exit();                    
            //     break;
            case 'save':
                // ながれ
                // ①チェックが付けられたブキのIDを格納した配列を作る
                // ②favoriteテーブルに既に登録されているぶきを↑の配列から取り除く
                // ③favoriteテーブルに格納する
                echo 'aaa';
                $msg = updateDB(connectDB(), $user_id, $_POST['checked']);

                break;
    
        }
    }
    // POST変数の中身を削除するなどの操作は特別必要ない多分
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DotGothic16&display=swap" rel="stylesheet">
    <title>お気に入りブキ</title>
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
        .user_command_button.act {    /* ボタンの見た目 親要素*/
            background-color: #cccccc;
            border-right: 0px;
            border-bottom: 0px;
            border-top: 1px solid #000000;
            border-left: 1px solid #000000;
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
<body bgcolor=#dfdfdf>
    <div class="all">
    <div class="h1">
        <a href="../index.php"><h1 style="margin-top:0;">武器ルーレット<span style="font-size: 18px;">（splatoon3専用）</span></h1></a>
    </div>
    <hr>
    <div class="login">                
    <?php
                        if(isset($_SESSION['login_with'])){
                            echo '<div style="display:flex; justify-content:space-between; margin:0 10px; height:24px;" class="switch_button sec1">';

                            // アイコンと文字をまとめるdiv
                            echo        '<div style="display:flex;">';
                            echo            '<div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#2ea65a;"></div>'; // カラーアイコン
                            echo            '<div><p style="margin:0;">'.$user_name.'でログイン中</p></div>'; // 文字
                            echo        '</div>';
                            // メニューボタンだけのdiv
                            echo        '<div>';
                            echo            '<img id="img1" src="../burger-icon.png" style="width:24px; height:24px; display:none;">';
                            echo            '<img id="img2" src="../ku-icon.png"     style="width:24px; height:24px;">';
                            echo        '</div>';
                            echo '</div>';


                            echo '<div style="margin:10px; display:none; display:flex; gap:0 6px;" class="switch sec1">';

                            echo        '<div style="width:calc(20% - 6px);" class="user_command_button">';
                            echo    '<form action="mylist.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾛｸﾞｱｳﾄ</p>';
                            echo            '<input type="hidden" name="user_command" value="logout">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style="width:calc(40% - 6px);" class="user_command_button">';
                            echo    '<form action="mylist.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ユーザ情報</p>';
                            echo            '<input type="hidden" name="user_command" value="profile">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style=width:calc(40% - 6px);" class="user_command_button act">';
                            echo    '<form action="mylist.php" method="POST">';                            
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">お気に入りブキ</p>';
                            echo            '<input type="hidden" name="user_command" value="favorite">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo '</div>';
                        }else{
                        }

                    ?>
    </div>

    <hr>
    <form action="./editmylist.php" method="POST">
        <div style="width:380px; margin-left:10px;">
            <div style="display:flex; justify-content:space-between; width:100%;">
                <h2>お気に入りブキにチェック</h2>
                <?php
                    if(isset($msg)){ // エラーメッセージがある場合
                        foreach($msg as $e){
                            echo '<p style="text-align:center; color:#e52860;">'.$e.'</p>';
                        }
                    }
                ?>
                <div style="display:flex;">
                    <!-- <div class="user_command_button" style="margin:16.6px 5px 0 0; width:50px; height:35px;"> -->
                        <!-- <form action="editprofile.php" method="POST"> -->
                            <!-- <p style="text-align:center; margin:5px; color:#000;">中止</p> -->
                            <!-- <input type="hidden" name="user_command" value="cancel"> -->
                            <!-- <button type="submit"></button> -->
                        <!-- </form> -->
                    <!-- </div> -->
                    <div class="user_command_button" style="margin-top:16.6px; width:50px; height:35px;">
                        <!-- <form action="editprofile.php" method="POST"> -->
                            <p style="text-align:center; margin:5px; color:#000;">保存</p>
                            <input type="hidden" name="user_command" value="save">
                            <button type="submit"></button>
                        <!-- </form> -->
                    </div>
                </div>
            </div>
            <?php // DBに登録されているすべての武器を並べて表示する
                for($i=0; $i<count($buki_data_all); $i++){
                    echo '<input type="checkbox" id="'.$i.'"';
                    echo 'name="checked[]"';
                    echo 'value="'.$i.'"';
                    if(in_array($buki_data_all[$i], $favorites)){ // お気に入り武器に登録されているブキだったとき
                        echo 'checked=""';
                    }
                    echo '>';
                    echo '<label for="'.$i.'"><strong>'.$buki_data_all[$i][0].'</strong><span style="font-size:13px">　'.$buki_data_all[$i][1].'</span></label><br>';
                }
            ?>
        </div>
    </form>
</body>
</html>


<?php
// DBを切断
unset($pdo);

$session_0 = $_SESSION['login_with'][0];
$session_1 = $_SESSION['login_with'][1];
echo "<script>console.log('ログイン状況： $session_0');</script>";
echo "<script>console.log('ログイン状況： $session_1');</script>";
?>