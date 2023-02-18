<?php

// タイムゾーン設定
date_default_timezone_set("Asia/Tokyo");

// 配列
$comment_array = array();
$error_messages = array();

// データベース接続
$pdo = new PDO('mysql:host=mysql1.php.xdomain.ne.jp;dbname=xd719957_db', xd719957_user, password);

// 投稿ボタンを押した時
if (!empty($_POST["submitButton"])){

        // 空白除去
	$view_name = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_name']);
	$message = preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);

    // 表示名の入力チェック
    if (empty($_POST["post_name"])) {
        $error_message[] = "お名前を入力してください。";
    } else {
        $escaped['post_name'] = htmlspecialchars($_POST["post_name"], ENT_QUOTES, "UTF-8");
    }

    // コメントの入力チェック
    if (empty($_POST["post_text"])) {
        $error_message[] = "コメントを入力してください。";
    } else {
        $escaped['post_text'] = htmlspecialchars($_POST["post_text"], ENT_QUOTES, "UTF-8");
    }
    // エラーメッセージが何もないときだけデータ保存できる
    if (empty($error_message) ) {

        // 日時取得
        $post_datetime = date("Y-m-d H:i:s");

        // トランザクション開始
        $pdo->beginTransaction();

        try {
        
            // dbに登録
            $stmt = $pdo->prepare("INSERT INTO `posts` (`post_name`, `post_text`, `post_datetime`) VALUES (:post_name, :post_text,:post_datetime);");
            $stmt->bindParam(':post_name', $_POST['post_name'],PDO::PARAM_STR);
            $stmt->bindParam(':post_text', $_POST["post_text"],PDO::PARAM_STR);
            $stmt->bindParam(':post_datetime', $post_datetime);

            $res = $stmt->execute();

            // db反映
            $res = $pdo->commit();

        } catch(Exception $e) {

            // エラーが発生した時は取り消し
            $pdo->rollBack();
        }

        if( $res ) {
            $success_message = 'メッセージを書き込みました。';
        } else {
            $error_message[] = '書き込みに失敗しました。';
        }

        header('Location: ./');
        exit;
    }
}

// GETで現在のページ数を取得する（未入力の場合は1を挿入）
if (isset($_GET['page'])) {
	$page = (int)$_GET['page'];
} else {
	$page = 1;
}

// スタートのポジションを計算する
if ($page > 1) {
	// 例：２ページ目の場合は、『(2 × 10) - 10 = 10』
	$start = ($page * 10) - 10;
} else {
	$start = 0;
}

// dbから降順で１０件データ取得
$sql = "SELECT `post_id`,`post_name`,`post_text`,`post_datetime` FROM `posts` ORDER BY `post_id` DESC LIMIT {$start}, 10";

// テーブルのデータ件数を取得する
$page_num = $pdo->prepare("SELECT COUNT(*) FROM `posts`");
$page_num->execute();
$page_num = $page_num->fetchColumn();

// ページネーションの数を取得する
$pagination = ceil($page_num / 10);

// 代入
$comment_array = $pdo->query($sql);

// dbの接続を閉じる
$pdo = null;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mybbs</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1 class="title">mybbs</h1>
    <hr>
    <div class="boardWrapper">
        <!-- メッセージ送信成功時 -->
        <?php if (empty($success_message)) : ?>
            <p class="success_message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <!-- バリデーションチェック時 -->
        <?php if (!empty($error_message)) : ?>
            <?php foreach ($error_message as $value) : ?>
                <div class="error_message">※<?php echo $value; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        <!-- コメント欄 -->
        <section>
            <?php foreach ($comment_array as $post_text) : ?>
                <article>
                    <div class="wrapper">
                        <div class="nameArea">
                            <span>name:</span>
                            <p class="post_name"><?php echo $post_text['post_name'] ?></p>
                            <time>:<?php echo $post_text['post_datetime']; ?></time>
                        </div>
                        <p class="post_text"><?php echo $post_text['post_text']; ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <!-- ページネーション表示 -->
        <?php for ($x=1; $x <= $pagination ; $x++) { ?>
	    <a href="?page=<?php echo $x ?>"><?php echo $x; ?></a>
        <?php } ?>
        <!-- 投稿欄 -->
        <form method="POST" class="formWrapper">
            <div>
                <input type="submit" value="投稿" name="submitButton">
                <label for="usernameLabel">name:</label>
                <input type="text" name="post_name">
            </div>
            <div>
                <textarea name="post_text" class="commentTextArea"></textarea>
            </div>
        </form>
    </div>

</body>

</html>