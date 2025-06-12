<?php
// 240804_0448 ログインするとできるようになることを説明するページ



// ------------------------------①ログイン状況を確認
session_start();
if($_SESSION['login_with'] != []){ // ログインできているときは、indexにリダイレクトしておく
    header("Location:./index.php");
    exit();
}

// ------------------------------------------------------------自身へのPOSTかつ、ボタン毎の処理分岐
if ($_SERVER['REQUEST_METHOD'] == 'POST') { // ポスト通信があったかを判別
    if (isset($_POST['user_command'])) {         // ボタンタグに付随するinputタグのname=""の部分　キー名自分で指定

        switch ($_POST['user_command']) {                 // ここからボタン毎の処理分岐
            case 'return':
                header("Location:./index.php");
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
    <title>ログインでできること</title>

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

        img {
            width: 374px;
            border: 3px solid #000;
        }

    </style>

</head>
<body bgcolor=#dfdfdf>



    <div class="all">
        <div class="h1">
            <a href="./index.php"><h1 style="margin-top:0;">ブキルーレット<span style="font-size: 18px;">（splatoon3専用）</span></h1></a>
        </div>
        <hr>
        <div class="">
            <div style="display:flex; justify-content:space-between; margin:0 10px; height:24px;" class="switch_button sec1">
                <div style="display:flex;">
                    <div style="width:20px; height:20px; margin:2px 4px 2px 0; background-color:#0a0a0a;"></div>
                    <div><p style="margin:0;">ゲストユーザで利用中</p></div>
                </div>
            </div>
        </div>
        <hr>

        <!-- ここに説明 -->

        <div style="width:380px; margin-left:10px; color:#0052d7;">
            <h2>ログインすると以下の機能がつかえます</h2>
            <ul>
                <li style="font-size:18px;"><strong>ユーザ名・ひとことの編集</strong></li>
                <p>
                    ユーザ名前（表示名） と ひとこと（ステータスメッセージ）を編集できるようになります。　<br>
                    他のユーザの情報も一覧で表示されます
                </p>
            </ul>
                <img src="./side-profile.png" alt="">    

            <ul>
            <br><br>
                <li style="font-size:18px;"><strong>お気に入りブキの登録</strong></li>
                <p>お気に入りブキを登録できるようになります（ログアウトしてもデータは保持されます）</p>
            </ul>
                <img src="./side-favorite.png" alt="">
            
            <ul>
            <br><br>
                <li style="font-size:18px;"><strong>絞り込み機能の強化</strong></li>
                <p>
                    登録したお気に入りブキでの絞り込みができるようになります
                </p>
            </ul>
                <img src="./side-lv.png" alt="">
            <ul>
                <p>
                ★おすすめの使い方↓<br>
                    ①どうしても苦手で使いたくないブキ以外をお気に入りブキとして登録する　<br>
                    ②絞り込みレベルをLv.3で回す　<br><br>
                    苦手なブキをはなから除外することができます                                            
                </p>
            </ul>
        </div>


<br><br>
        <div id="aaa" class="user_command_button" style="width:120px; height:45px;">
            <form action="side.php" method="POST">
                <p style="text-align:center; margin:5px; font-size:20px;">戻る</p>
                <input type="hidden" name="user_command" value="return">
                <button type="submit"></button>
            </form>
        </div>
        <br><br>
        <br><br>


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