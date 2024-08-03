<?php
// 自身のプロフィールを確認する画面
// 流れ
// ①ログイン状況を確認（user_idまたはuser_name）を経数に入れておく
// ②DB接続
// ③usersテーブルから、user_idまたはuser_nameに対応するユーザのデータを取得する
// ④表示する

// ------------------------------①ログイン状況を確認
session_start();
if($_SESSION['login_with'] != []){ // ログインできているときのみ動く
    $user_id = $_SESSION['login_with'][0];
}else{
    header("Location:../index.php");
    exit();
}
// ユーザ情報確認用
echo "<script>console.log('user_id: $user_id');</script>";
// echo "<script>console.log('user_name: $user_name');</script>";


// ------------------------------②DBへ接続する
$password = getenv('DB_PASSWORD');
try {
    $pdo = new PDO('mysql:dbname=floor02_sp3-bk-roulette;host=mysql652.db.sakura.ne.jp;', 'floor02', $password);
    // echo 'DB接続成功<br>';
  } catch (PDOException $e) {
    // echo "DB接続失敗<br>: " . $e->getMessage();
  }

// ------------------------------③データを取得
$pdo->query('SET NAMES utf8;'); // 文字化け回避
// usersテーブルから全てのカラム（ユーザid, ユーザ名, ｻｲﾝｱｯﾌﾟ日時, ラストログイン日時, 一言）を取得する
$stmt = $pdo->prepare('SELECT user_id, user_name, signup_date, last_login, hitokoto FROM users WHERE user_id = :user_id');
$stmt->bindParam(':user_id', $user_id); // プレースホルダにバインド（セキュリティ上必要）
$stmt->execute(); // SQL実行
$user_data = $stmt->fetchAll();


// DBのデータを変数に入れる
$user_id = $user_data[0]['user_id'];
$user_name = $user_data[0]['user_name'];
$hitokoto = $user_data[0]['hitokoto'];
$signup_date = $user_data[0]['signup_date'];
$last_login = $user_data[0]['last_login'];

// ユーザ情報確認用
echo "<script>console.log('user_id: $user_id');</script>";
echo "<script>console.log('user_name: $user_name');</script>";
echo "<script>console.log('hitokoto: $hitokoto');</script>";
echo "<script>console.log('signup_date: $signup_date');</script>";
echo "<script>console.log('last_login: $last_login');</script>";

// ------------------------------その他ユーザ情報を取得する240802_0834
$pdo->query('SET NAMES utf8;'); // 文字化け回避
// usersテーブルから全てのカラム（ユーザid, ユーザ名, ｻｲﾝｱｯﾌﾟ日時, ラストログイン日時, 一言）を取得する
$stmt = $pdo->prepare('SELECT user_name, hitokoto FROM users');
$stmt->execute(); // SQL実行
$other_user_data = $stmt->fetchAll();


// ------------------------------------------------------------ログイン系処理
// ------------------------------------------------------------自身へのPOSTかつ、ボタン毎の処理分岐
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // ポスト通信があったかを判別
    if (isset($_POST['user_command'])) {         // ボタンタグに付随するinputタグのname=""の部分　キー名自分で指定
        $command = $_POST['user_command'];       // キー名を変数に入れておく。
        echo "<script>console.log('user_command: $command');</script>";
        switch ($command) {                 // ここからボタン毎の処理分岐
            case 'logout':                 // 例えばinputタグのvalue=""の部分が trigger ならここが動作。
                header("Location:./logout.php");
                exit();                    
                break;
            case 'profile':
                header("Location:./profile.php");
                exit();                    
                break;
            case 'favorite':
                header("Location:../Favorite/mylist.php");
                exit();                        
                break;
            case 'edit-profile':
                header("Location:../Favorite/editprofile.php");
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
    <title>ユーザ情報</title>
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
                            echo    '<form action="profile.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ﾛｸﾞｱｳﾄ</p>';
                            echo            '<input type="hidden" name="user_command" value="logout">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style="width:calc(40% - 6px);" class="user_command_button act">';
                            echo    '<form action="profile.php" method="POST">';
                            echo            '<p style="text-align:center; margin:9px 0; font-size:16px;">ユーザ情報</p>';
                            echo            '<input type="hidden" name="user_command" value="profile">';
                            echo            '<button type="submit"></button>';
                            echo    '</form>';
                            echo        '</div>';

                            echo        '<div style=width:calc(40% - 6px);" class="user_command_button">';
                            echo    '<form action="profile.php" method="POST">';                            
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
        <div style="width:380px; margin-left:10px;">
            <div style="display:flex; justify-content:space-between; width:100%;">
                <h2>あなたのプロフィール</h2>
                <div class="user_command_button" style="margin-top:16.6px; width:50px; height:35px;">
                    <form action="editprofile.php" method="POST">
                        <p style="text-align:center; margin:5px;">編集</p>
                        <input type="hidden" name="user_command" value="edit_profile">
                        <button type="submit"></button>
                    </form>
                </div>
            </div>

            <table>
                <tr>
                    <td style="width:80px;"><strong>ユーザID</strong></td>
                    <td><?php echo $user_id; ?></td>
                </tr>
                <tr>
                    <td style="width:80px;"><strong>ユーザ名</strong></td>
                    <td><?php echo $user_name; ?></td>
                </tr>
                <tr>
                    <td style="width:80px;"><strong>ひとこと</strong></td>
                    <td><?php echo $hitokoto; ?></td>
                </tr>
                <tr>
                    <td style="width:80px;"><strong>初ﾛｸﾞｲﾝ</strong></td>
                    <td><?php echo $signup_date; ?></td>
                </tr>
                <tr>
                    <td style="width:80px;"><strong>前回ﾛｸﾞｲﾝ</strong></td>
                    <td><?php echo $last_login; ?></td>
                </tr>
            </table>

            <hr>
            <div style="display:flex; justify-content:space-between; width:100%;">
                <h2>登録済みユーザ一覧</h2>
            </div>

                <table style="width:100%;">
                    <tr style="">
                        <td style="border: 1px solid black; width:180px; text-align:center; padding:5px 0;"><strong>ユーザ名</strong></td>
                        <td style="border: 1px solid black; text-align:center; padding:5px 0;"><strong>ひとこと</strong></td>
                    </tr>
                <?php
                    for($i=0; $i<count($other_user_data); $i++){
                        echo '<tr>';
                        echo    '<td style="text-align:center; padding:3px 0; border-bottom: 1px dashed black;">'.$other_user_data[$i][0].'</td>';
                        echo    '<td style="padding:3px 0; border-bottom: 1px dashed black;"><span style="font-size:12px;">'.$other_user_data[$i][1].'</span></td>';
                        echo '</tr>';
                    }
                ?>
                </table>            
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