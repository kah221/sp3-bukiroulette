<?php
// 240724_0222　ログインするというボタンが押されたときに開く画面
// ながれ
// ①入力されたユーザ名、パスワードを変数に代入
// ②DB接続
// ③ユーザ名に対応するデータをusersテーブルから取得する
// ④入力されたユーザ名がＤＢに存在しているかどうかを判断
// ⑤存在していれば、パスワードを照合する
// ⑥パスワード一致ならユーザIDとユーザ名をセッション変数に入れる

// DBへ接続する
function connectDB(){
    $password = getenv('DB_PASSWORD');
    try {
        $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', $password);
    } catch (PDOException $e) {echo "DB接続失敗<br>: " . $e->getMessage();}
    return $pdo;
}


// ------------------------------
// 入力された情報を取得
function compareDBUserData($pdo, $input_name, $input_password){
    echo "<script>console.log('compareDBUserNameが呼び出された');</script>";
    $msg = []; // ﾛｸﾞｲﾝ試行に対する結果のメッセージを格納
    
    // ③ユーザ名に対応するデータを取得する -> target_user
    $pdo->query('SET NAMES utf8;'); // 文字化け回避
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_name = :input_name'); // 実行するSQLを用意
    $stmt->bindParam(':input_name', $input_name);
    $stmt->execute(); // SQLを実行
    $target_user = $stmt->fetchAll(); // 結果を取得

    // 表示用
    $target_user_id = $target_user[0]['user_id'];
    $target_user_name = $target_user[0]['user_name'];
    $target_user_password = $target_user[0]['password'];
    echo "<script>console.log('target_user_id: $target_user_id');</script>";
    echo "<script>console.log('target_user_name: $target_user_name');</script>";
    echo "<script>console.log('target_user_pssword: $target_user_password');</script>";
    
    // target_userが array(0) {}の時は、一致するユーザが存在しなかったことになる
    if($target_user == []){ // ユーザ名が存在しない
        array_push($msg, '入力されたユーザ名は登録されていません');
    }else{ // ユーザ名が存在
        array_push($msg, '登録されたユーザ名であることを確認');
        // パスワード照合
        if($input_password == $target_user_password){ // パスワード一致
            // 最終ログイン時刻を更新
            $ip_address = $_SERVER['REMOTE_ADDR'];  // ipアドレス
            date_default_timezone_set('Asia/Tokyo');
            $dt = new DateTime();
            $dt_strict = $dt -> format("Y-m-d H:i:s"); // usersのsignup_dateに格納するための厳密なdatetime型

            // ログインIPアドレスを更新
            $stmt = $pdo->prepare('UPDATE users SET login_ip_address = :ip_address WHERE user_id = :target_user_id');
            $stmt->bindValue(':ip_address', $ip_address);
            $stmt->bindValue(':target_user_id', $target_user_id);
            $stmt->execute(); // SQLを実行

            // ログイン時刻を更新
            $stmt = $pdo->prepare('UPDATE users SET last_login = :dt_strict WHERE user_id = :target_user_id');
            $stmt->bindValue(':dt_strict', $dt_strict);
            $stmt->bindValue(':target_user_id', $target_user_id);
            $stmt->execute(); // SQLを実行

            session_start();
            $_SESSION['login_with'] = [$target_user_id, $target_user_name];
            header("Location:../index.php");    // もうそのままindexへ飛ばしてしまう
            exit();
        }else{ // パスワード不一致
            array_push($msg, 'パスワードが間違っています');
        }
    }   
    // foreach($msg as $e){
    //     echo $e . '<br>';
    // }
    return $msg;
}






// ------------------------------------------------------------自身へのPOSTかつ、ボタン毎の処理分岐
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // ポスト通信があったかを判別
    if (isset($_POST['user_command'])) {         // ボタンタグに付随するinputタグのname=""の部分　キー名自分で指定
        $command = $_POST['user_command'];       // キー名を変数に入れておく。
        $input_name = $_POST['input_name'];
        $input_password = $_POST['input_password'];
        echo "<script>console.log('user_command: $command');</script>";
        echo "<script>console.log('input_name: $input_name');</script>";
        echo "<script>console.log('input_password: $input_password');</script>";

        switch ($command) {                 // ここからボタン毎の処理分岐
            case 'jump_signup':
                header("Location:./signup.php");
                exit();                    
                break;
            case 'login':  // ﾛｸﾞｲﾝボタンが押されたとき
                $msg = compareDBUserData(connectDB(), $_POST['input_name'], $_POST['input_password']);// 第1引数はDBに接続しpdoを返してくる関数
                break;
            }
    }
    // POST変数の中身を削除するなどの操作は特別必要ない多分
}
?>
<!-- ------------------------------ -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DotGothic16&display=swap" rel="stylesheet">
    <title>ログイン画面</title>

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
        td {
            text-align: left;
            vertical-align: top;
            height: 50px;
        }
        li {
            margin-top: 3px;
        }
        #aaa {
            margin-left:130px;
        }
    </style>

</head>
<body bgcolor=#dfdfdf>



    <div class="all">
    <div class="h1">
        <a href="../index.php"><h1 style="margin-top:0;">ブキルーレット<span style="font-size: 18px;">（splatoon3専用）</span></h1></a>
    </div>
    <hr>
    <div class="login">                
        <div style="display:flex; justify-content:space-between; margin:0 10px; height:24px;" class="switch_button sec1">
            <div style="display:flex;">
                <div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#0a0a0a;"></div>
                <div><p style="margin:0;">ユーザログイン試行中</p></div>
            </div>
        </div>
    </div>

    <hr>
        <div style="width:380px; margin-left:10px;">
            <div style="display:flex; justify-content:space-between; width:100%;">
                <h2>ログインする　　<span style="font-size:60%;">新規ユーザなら...→</span></h2>
                <div class="user_command_button" style="margin-top:16.6px; width:90px; height:35px;">
                    <form action="login.php" method="POST">
                        <p style="text-align:center; margin:5px;">登録画面ﾍ</p>
                        <input type="hidden" name="user_command" value="jump_signup">
                        <button type="submit"></button>
                    </form>
                </div>
            </div>

            
            <div>
                <form action="login.php" method="POST">
                    <table>
                        <tr>
                            <td style="width:80px;">ユーザ名</td>
                            <td>
                                <input 
                                type="text" 
                                id="user_name" 
                                name="input_name"
                                required maxlength="10"
                                pattern="^[a-zA-Z0-9ぁ-んァ-ヶー一-龠]*$"
                                >
                                <p style="font-size:13px;">　10文字以内</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:80px;">パスワード</td>
                            <td>
                                <input 
                                type="password" 
                                id="password" 
                                name="input_password"
                                required maxlength="20"
                                pattern="[a-zA-Z0-9]{1,10}"
                                >
                                <p style="font-size:13px;">　20文字以内</p>
                                <p style="font-size:13px;">　半角アルファベットと数字のみ</p>
                            </td>
                        </tr>
                    </table>

                    <div id="aaa" class="user_command_button" style="margin-top:16.6px; width:120px; height:45px;">
                    <p style="text-align:center; margin:5px; font-size:20px;">ﾛｸﾞｲﾝ</p>
                        <input type="hidden" name="user_command" value="login">
                        <button type="submit"></button>
                    </div>
                </form>
            </div>

            <?php
                foreach($msg as $e){
                    echo '<p style="text-align:center; color:#e52860;">'.$e.'</p>';
                }
            ?>

        </div>
    </div>





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