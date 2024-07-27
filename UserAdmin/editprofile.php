<?php
// 自身のプロフィールを編集するときに開く画面
// ポップアップにした方がいいかも
// 240726_1821　ログインするというボタンが押されたときに開く画面
// 流れ
// ①ログイン状況を確認、ユーザIDを取得する
// ②DBに接続、usersテーブルからユーザIDに対応するユーザデータだけを取得する
// ③

// ------------------------------①ログイン状況を確認
session_start();
if($_SESSION['login_with'] != []){ // ログインできているときのみ動く
    $user_id = $_SESSION['login_with'][0];
}else{
    header("Location:../index.php");
    exit();
}

// ------------------------------②DBへ接続する
$password = getenv('DB_PASSWORD');
try {
    $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', $password);
    // echo 'DB接続成功<br>';
} catch (PDOException $e) {
    // echo "DB接続失敗<br>: " . $e->getMessage();
}
// ------------------------------③ログイン中のユーザデータのみを取得
$pdo->query('SET NAMES utf8;'); // 文字化け回避
$stmt = $pdo->prepare('SELECT user_id, user_name, signup_date, last_login, hitokoto FROM users WHERE user_id = :user_id'); // usersテーブルからユーザのデータを取得
$stmt->bindParam(':user_id', $user_id); // プレースホルダにバインド（セキュリティ上必要）
$stmt->execute(); // SQL実行
$login_user = $stmt->fetchAll();

// DBのデータを変数に入れる
$user_id = $login_user[0]['user_id'];
$user_name = $login_user[0]['user_name'];
$hitokoto = $login_user[0]['hitokoto'];
$signup_date = $login_user[0]['signup_date'];
$last_login = $login_user[0]['last_login'];

// ------------------------------プロフィール編集用関数
function editDBUserName($new_name){ // ユーザ名
    $msg = [];
    global $pdo;    // 関数内でDB操作をするにはスコープを
    global $user_id;
    global $user_name;
    $result = $user_name;  // 最終的にDBに登録したユーザ名を格納して返す

    try {
        $stmt = $pdo->prepare('SELECT user_name FROM users');
        $stmt->execute();
        $db_registered_user_names = $stmt->fetchAll();

        // エラーが発生した場合、catchブロックへ
        if (!$stmt) {
            throw new Exception('SQL文の実行に失敗しました');
        }
    } catch (PDOException $e) {
        echo 'データベースエラー: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'その他のエラー: ' . $e->getMessage();
    }

    // 余計な部分を取り除く（キー名が数値の部分が不要）
    $registered_user_names = []; // DBに登録されている名前一覧
    for($i=0; $i<count($db_registered_user_names); $i++){
        foreach($db_registered_user_names[$i] as $key => $value){
            if(gettype($key) == 'string'){
                array_push($registered_user_names, $value);
            }
        }
    }    
    // var_dump($registered_user_names);

    echo '<br>書き換え対称のユーザID: '.$user_id.'<br>';
    echo '書き換え対称のユーザ名: '.$user_name.'<br>';
    echo '新しく入力されたユーザ名: '.$new_name.'<br>';

    if (in_array($new_name, $registered_user_names) == false){ // DBに存在しないとき　かきかえOK
        // DB書き換え
        $stmt = $pdo->prepare('UPDATE users SET user_name = :new_name WHERE user_id = :user_id');
        $stmt->bindValue(':new_name', $new_name);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        if (!$stmt->execute()) {
            // エラー処理
            print_r($stmt->errorInfo());
        } else {
            // 更新成功時の処理
            $result = $new_name;
        }

    }else{ // DBに同じ名前が既にあり、
        if($new_name != $user_name){ // 他のユーザと一致している場合
            array_push($msg, '既に同じ名前のユーザがいるため登録できません');
        }
    }
    // エラーメッセ―ジ
    foreach($msg as $e){
        echo 'msg: '.$e.'<br>';
    }
    return $result;
}

function editDBHitokoto($new_hitokoto){ // ひとこと
    $msg = [];
    global $pdo;
    global $user_id;
    global $hitokoto;

    // usersテーブルの対象のユーザIDに対応するhitokotoを書き換える
    try {
        $stmt = $pdo->prepare('UPDATE users SET hitokoto = :new_hitokoto WHERE user_id = :user_id');
        $stmt->bindValue(':new_hitokoto', $new_hitokoto);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        if (!$stmt->execute()) {
            // エラー処理
            print_r($stmt->errorInfo());
        } else {
            // 更新成功時の処理
        }

        // エラーが発生した場合、catchブロックへ
        if (!$stmt) {
            throw new Exception('SQL文の実行に失敗しました');
        }
    } catch (PDOException $e) {
        echo 'データベースエラー: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'その他のエラー: ' . $e->getMessage();
    }
}

// ------------------------------------------------------------自身へのPOSTかつ、ボタン毎の処理分岐
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // ポスト通信があったかを判別
    if (isset($_POST['user_command'])) {         // ボタンタグに付随するinputタグのname=""の部分　キー名自分で指定
        $command = $_POST['user_command'];       // キー名を変数に入れておく。
        $input_name = $_POST['input_name'];
        $input_hitokoto = $_POST['input_hitokoto'];
        echo "<script>console.log('user_command: $command');</script>";
        echo "<script>console.log('input_name: $input_name');</script>";
        echo "<script>console.log('input_hitokoto: $input_hitokoto');</script>";

        switch ($command) {                 // ここからボタン毎の処理分岐
            case 'cancel':                 // 例えばinputタグのvalue=""の部分が trigger ならここが動作。
                header("Location:./profile.php");
                exit();                    
                break;
            case 'save':  // 保存ボタンが押されたとき
                $result_user_name = editDBUserName($_POST['input_name']);
                editDBHitokoto($_POST['input_hitokoto']);
                $_SESSION['login_with'][1] = $result_user_name;  // ログイン状況のIDとuser_nameのうち表示名の部分だけ更新
                header("Location:./profile.php");
                exit();                    
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
    <title>プロフィール編集</title>
    <style>
        body {
            background-color: #dfdfdf;
            font-family: 'DotGothic16', sans-serif;
            color: #e52860;
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

        .user_command_button.block {    /* ボタンの見た目 親要素*/
            background-color: #cccccc;
        }
        .user_command_button.act {    /* ボタンの見た目 親要素*/
            background-color: #cccccc;
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
        <h1 style="margin-top:0;">武器ルーレット<span style="font-size: 18px;">（splatoon3専用）</span></h1>
    </div>
    <hr>
    <div class="login">                
    <?php
        if(isset($_SESSION['login_with'])){
            echo '<div style="display:flex; justify-content:space-between; margin:0 10px; height:24px;" class="switch_button sec1">';

            // アイコンと文字をまとめるdiv
            echo        '<div style="display:flex;">';
            echo            '<div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#2ea65a;"></div>'; // カラーアイコン
            echo            '<div style="height:20px;"><p style="margin:0;">'.$user_name.'でログイン中</p></div>'; // 文字
            echo        '</div>';
            // メニューボタンだけのdiv
            echo        '<div>';
            echo            '<img id="img1" src="../burger-icon.png" style="width:24px; height:24px; display:none;">';
            echo            '<img id="img2" src="../ku-icon.png"     style="width:24px; height:24px;">';
            echo        '</div>';
            echo '</div>';


            echo '<div style="margin:10px; display:none; display:flex; gap:0 6px;" class="switch sec1">';

            echo        '<div style="width:calc(20% - 6px);" class="user_command_button block">';
            echo            '<p style="text-align:center; margin:9px 0; font-size:16px; text-decoration:line-through;">ﾛｸﾞｱｳﾄ</p>';
            echo            '<button type="submit"></button>';
            echo        '</div>';

            echo        '<div style="width:calc(40% - 6px);" class="user_command_button block act">';
            echo            '<p style="text-align:center; margin:9px 0; font-size:16px; text-decoration:line-through;">プロフィール</p>';
            echo            '<button type="submit"></button>';
            echo        '</div>';

            echo        '<div style=width:calc(40% - 6px);" class="user_command_button block">';
            echo            '<p style="text-align:center; margin:9px 0; font-size:16px; text-decoration:line-through;">お気に入りブキ</p>';
            echo            '<button type="submit"></button>';
            echo        '</div>';

            echo '</div>';
        }else{
        }

    ?>
    </div>

    <hr>
    <form action="./editprofile.php" method="POST">
        <div style="width:380px; margin-left:10px;">
            <div style="display:flex; justify-content:space-between; width:100%;">
                <h2>プロフィール編集</h2>
                <div style="display:flex;">
                    <div class="user_command_button" style="margin:16.6px 5px 0 0; width:50px; height:35px;">
                        <!-- <form action="editprofile.php" method="POST"> -->
                            <p style="text-align:center; margin:5px; color:#000;">中止</p>
                            <input type="hidden" name="user_command" value="cancel">
                            <button type="submit"></button>
                        <!-- </form> -->
                    </div>
                    <div class="user_command_button" style="margin-top:16.6px; width:50px; height:35px;">
                        <!-- <form action="editprofile.php" method="POST"> -->
                            <p style="text-align:center; margin:5px; color:#000;">保存</p>
                            <input type="hidden" name="user_command" value="save">
                            <button type="submit"></button>
                        <!-- </form> -->
                    </div>
                </div>
            </div>
            
            <table>
                <tr>
                    <td style="width:80px;">ユーザID</td>
                    <td><?php echo $user_id; ?></td>
                </tr>
                <!-- input_nameとinput_hitokoto　というキー名で値を送信する -->
                <tr>
                    <td style="width:80px;">ユーザ名</td>
                    <td>
                        <input 
                        type="text" 
                        name="input_name"
                        placeholder="<?php echo $user_name; ?>" 
                        value="<?php echo $user_name; ?>"
                        required maxlength="10"
                        pattern="^[a-zA-Z0-9ぁ-んァ-ヶー一-龠]*$"
                        >
                    </td>
                    <td>(10文字以内)</td>
                </tr>
                <tr>
                    <td style="width:80px;">ひとこと</td>
                    <td>
                        <input 
                        type="text" 
                        name="input_hitokoto"
                        placeholder="<?php echo $hitokoto; ?>" 
                        value="<?php echo $hitokoto; ?>"
                        required maxlength="20"
                        pattern="^[a-zA-Z0-9ぁ-んァ-ヶー一-龠]*$"
                        >
                    </td>
                    <td>(20文字以内)</td>
                </tr>
                <tr>
                    <td style="width:80px;">初ﾛｸﾞｲﾝ</td>
                    <td><?php echo $signup_date; ?></td>
                </tr>
                <tr>
                    <td style="width:80px;">前回ﾛｸﾞｲﾝ</td>
                    <td><?php echo $last_login; ?></td>
                </tr>
            </table>
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