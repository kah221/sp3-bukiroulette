<?php
// 大量に作られるのを防ぐために1にち1垢しか作れないようにしたい　日付をセッション変数に入れる
// 流れ
// ①ログイン状況を確認し、ログイン中ならindexへ飛ばす
// ②ユーザ名とパスワードを入力させる（バリデーションは簡単にHTML5の機能を使う）
// ③ユーザ名に被りが無いかを確認する
// ④DBに登録する
// ⑤indexへ飛ばす



// ------------------------------①ログイン状況を確認
session_start();
if($_SESSION['login_with'] != []){
    header("Location:../index.php");
    exit();
}
// ユーザ情報確認用
echo "<script>console.log('user_id: $user_id');</script>";


// DBへ接続する
function connectDB(){
    $password = getenv('DB_PASSWORD');
    try {
        $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', $password);
    } catch (PDOException $e) {echo "DB接続失敗<br>: " . $e->getMessage();}
    return $pdo;
}



// ------------------------------③ログイン中のユーザデータのみを取得
// $pdo->query('SET NAMES utf8;'); // 文字化け回避
// $stmt = $pdo->prepare('SELECT user_id, user_name, signup_date, last_login, hitokoto FROM users WHERE user_id = :user_id'); // usersテーブルからユーザのデータを取得
// $stmt->bindParam(':user_id', $user_id); // プレースホルダにバインド（セキュリティ上必要）
// $stmt->execute(); // SQL実行
// $login_user = $stmt->fetchAll();

// // DBのデータを変数に入れる
// $user_id = $login_user[0]['user_id'];
// $user_name = $login_user[0]['user_name'];
// $hitokoto = $login_user[0]['hitokoto'];
// $signup_date = $login_user[0]['signup_date'];
// $last_login = $login_user[0]['last_login'];

// ------------------------------プロフィール編集用関数
// 入力された情報を取得
function compareDBUserData($pdo, $input_name, $input_password){
    echo "<script>console.log('compareDBUserNameが呼び出された');</script>";
    $msg = []; // アカウント登録の試行に対する結果のメッセージを格納
    
    // ③入力されたユーザ名と同じデータを取得 -> same_user
    $pdo->query('SET NAMES utf8;'); // 文字化け回避
    $stmt = $pdo->prepare('SELECT user_name FROM users WHERE user_name = :input_name'); // 実行するSQLを用意
    $stmt->bindParam(':input_name', $input_name);
    $stmt->execute(); // SQLを実行
    $same_user = $stmt->fetchAll(); // 結果を取得

    // 表示用
    $same_user_name = $same_user[0]['user_name'];
    echo "<script>console.log('same_user_name: $same_user_name');</script>";
    
    // same_userが array(0) {}の時は、一致するユーザが存在しなかったことになる
    if($same_user == []){ // ユーザ名が存在しない
        // 登録準備 登録に必要な情報を取得
        $ip_address = $_SERVER['REMOTE_ADDR'];  // ipアドレス
        date_default_timezone_set('Asia/Tokyo');
        $dt = new DateTime();
        $datetime = $dt -> format("Ymd");
        $dt_strict = $dt -> format("Y-m-d H:i:s"); // usersのsignup_dateに格納するための厳密なdatetime型
        // echo $datetime.'■<br>';




        // ------------------------------signupへ登録した日時を入れる　初めてのIPアドレスの時はINSERT 2回目以降のIPアドレスの時はUPDATE
        // 今回のIPアドレスが存在するか確認
        $stmt = $pdo->prepare('SELECT * FROM signup WHERE ip_address = :ip_address');
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->execute(); // SQLを実行
        $exist = $stmt->fetchAll(); // このexistには1つまたは0つのIPアドレスと時刻　のペアが入るはず。
        // 存在判定
        if($exist == []){ // 空の配列なら初めて　INSERT
            $stmt = $pdo->prepare('INSERT INTO signup (ip_address, signup_datetime) VALUES (:ip_address, :signup_datetime)');
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':signup_datetime', $datetime);
            $stmt->execute(); // SQLを実行
            // エラー処理
            if ($stmt->rowCount() == 0) {
                array_push($msg, 'データベースへの登録に失敗しました');
                array_push($msg, 'もう一度試すか、制作者にご連絡ください');
            } else {
                array_push($msg, 'signupへの新規登録に成功しました！');
            }

            // ------------------------------usersへ新規ユーザデータを挿入する
            $stmt = $pdo->prepare('INSERT INTO users (user_name, password, ip_address, signup_date) VALUES (:input_name, :input_password, :ip_address, :signup_date)');
            $stmt->bindParam(':input_name', $input_name);
            $stmt->bindParam(':input_password', $input_password);
            $stmt->bindParam(':ip_address', $ip_address);
            $stmt->bindParam(':signup_date', $dt_strict);
            $stmt->execute(); // SQLを実行
            // エラー処理
            if ($stmt->rowCount() == 0) {
                array_push($msg, 'データベースへの登録に失敗しました');
                array_push($msg, 'もう一度試すか、制作者にご連絡ください');
            } else {
                array_push($msg, 'userへの新規登録に成功しました！');
            }


        }else{ // 空ではないなら2回目以降　UPDATEだが、その前に1日経過しているかどうかの確認
            // echo $datetime.'<br>';
            // echo $exist[0]['signup_datetime'].'<br>';

            if($datetime == $exist[0]['signup_datetime']){ // 同じとき　ダメ （文字列比較）
                array_push($msg, '本日の新規登録回数の上限のため失敗しました');
            }else{ // 違う時　おけ
                array_push($msg, '本日1回目の登録でした');
                // signupテーブルも更新しておく UPDATE
                $stmt = $pdo->prepare('UPDATE users SET user_name = :new_name WHERE user_id = :user_id');
                $stmt = $pdo->prepare('UPDATE signup SET signup_datetime = :datetime WHERE ip_address = :ip_address');
                $stmt->bindValue(':datetime', $datetime);
                $stmt->bindValue(':ip_address', $ip_address);
                $stmt->execute();

                // ------------------------------usersへ新規ユーザデータを挿入する
                $stmt = $pdo->prepare('INSERT INTO users (user_name, password, ip_address) VALUES (:input_name, :input_password, :ip_address)');
                $stmt->bindParam(':input_name', $input_name);
                $stmt->bindParam(':input_password', $input_password);
                $stmt->bindParam(':ip_address', $ip_address);
                $stmt->execute(); // SQLを実行
                // エラー処理
                if ($stmt->rowCount() == 0) {
                    array_push($msg, 'データベースへの登録に失敗しました');
                    array_push($msg, 'もう一度試すか、制作者にご連絡ください');
                } else {
                    array_push($msg, 'userへの新規登録に成功しました！');
                }
            }


        }



    }else{ // ユーザ名が存在　登録不可能！
        array_push($msg, '同じユーザ名が既に登録されています');
    }
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
            case 'jump_login':
                header("Location:./login.php");
                exit();                    
                break;
            case 'register':  // 登録ボタンが押されたとき
                $msg = compareDBUserData(connectDB(), $_POST['input_name'], $_POST['input_password']);// 第1引数はDBに接続しpdoを返してくる関数
                // compareDBUserName($_POST['input_name']);
                // registerNewUser();
                // header("Location:./profile.php");
                // exit();                    
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DotGothic16&display=swap" rel="stylesheet">
    <title>アカウント登録</title>

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
                <div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#0052d7;"></div>
                <div><p style="margin:0;">ユーザ新規登録中</p></div>
            </div>
        </div>
    </div>

    <hr>
        <div style="width:380px; margin-left:10px;">
            <div style="display:flex; justify-content:space-between; width:100%;">
                <h2>新規登録　　　　<span style="font-size:60%;">既に登録済みなら...→</span></h2>
                <div class="user_command_button" style="margin-top:16.6px; width:90px; height:35px;">
                    <form action="signup.php" method="POST">
                        <p style="text-align:center; margin:5px;">ﾛｸﾞｲﾝ画面ﾍ</p>
                        <input type="hidden" name="user_command" value="jump_login">
                        <button type="submit"></button>
                    </form>
                </div>
            </div>

            
            <div>
                <form action="signup.php" method="POST">
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
                    
                    <div class="rule" style="color:#0052d7;">
                        <h2>仕様／ルール</h2>
                        <ul>
                            <li>ユーザ名はあとから変更可能です</li>
                            <li>他のユーザと同じユーザ名は付けられません</li>
                            <li>パスワードはあとから変更できません</li>
                            <li>パスワードリセット機能はないので、忘れないように気を付けてください</li>
                            <li>公序良俗に反する文字の利用を確認した場合はユーザを削除します</li>
                            <li>新規登録は連続で行えません</li>
                        </ul>
                    </div>
        
                    <!-- <div class="user_command_button" style="width:120px; height:45px; margin-left:calc((100% - 380px) / 2);"> -->
                    <div id="aaa" class="user_command_button" style="width:120px; height:45px;">
                        <p style="text-align:center; margin:5px; font-size:20px;">登録</p>
                        <input type="hidden" name="user_command" value="register">
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