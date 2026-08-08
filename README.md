# BookShelf 書籍レビューアプリ

書籍の登録、レビュー、お気に入り、ランキングなどの機能を実装したLaravelプロジェクトです。

一般ユーザーが書籍やレビューを投稿できるほか、Google Books APIを利用したISBN検索、マイ読書レポート、読書計画、期限に応じたリマインダー通知を利用できます。また、外部アプリケーション向けに書籍情報を操作する公開APIを実装し、書き込み系エンドポイントをLaravel Sanctumで保護しています。

## 作成者

takeda taisei

## 使用技術

- PHP 8.5

- Laravel 10.x

- MySQL 8.4

- Docker / Docker Compose / Laravel Sail

- Vite 5

- Tailwind CSS 3.4

- Alpine.js

- Laravel Fortify（Web認証）

- Laravel Sanctum（APIトークン認証）

- Google Books API

- PHPUnit 10

- phpMyAdmin

## ER図

![ER図](docs/book-shelf-er.png)

## 開発環境URL

- アプリケーション: http://localhost

- phpMyAdmin: http://localhost:8080

## 動作環境

- Docker

- Docker Compose

## 環境構築手順

1. リポジトリをクローン

```
git clone https://github.com/taisei1208/bookshelf-app.git
cd bookshelf-app
```

2. .envファイルの準備

.env.exampleをコピーして.envを作成します。

```
cp .env.example .env
```

.env.exampleのデフォルト値はSail向けではないため、.env内のDB接続情報を次のように変更してください。

```
APP_NAME=BookShelf
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

FORWARD_DB_PORT=3306
FORWARD_PHPMYADMIN_PORT=8080
```

ISBN検索を利用する場合は、Google Cloud ConsoleでBooks APIを有効にし、取得したAPIキーを設定します。

GOOGLE_BOOKS_API_URL=https://www.googleapis.com/books/v1/volumes
GOOGLE_BOOKS_API_KEY=取得したAPIキー

.envにはAPIキーやパスワードが含まれるため、Gitへ追加しないでください。

3. Composer依存パッケージのインストール

プロジェクトの初回セットアップ時はvendorディレクトリが存在せず、Sailコマンドを使用できません。以下のDockerコマンドを実行して、コンテナ内でcomposer installを実行します。

```
docker run --rm \
 -u "$(id -u):$(id -g)" \
 -v "$(pwd):/var/www/html" \
 -w /var/www/html \
 laravelsail/php85-composer:latest \
 composer install --ignore-platform-reqs
```

4. Laravel Sailの起動

以下のコマンドでDockerコンテナを起動します。

```
./vendor/bin/sail up -d
```

5. エイリアスの設定（推奨）

毎回./vendor/bin/sailと入力する代わりに、エイリアスを設定できます。

```
alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
```

以降の手順では、エイリアスを設定したものとしてsailと記載します。設定していない場合は./vendor/bin/sailへ読み替えてください。

6. アプリケーションキーの生成

sail artisan key:generate

Google Books APIの設定を変更した場合は、設定キャッシュを削除します。

sail artisan config:clear

7. データベースのマイグレーションと初期データ投入

以下のコマンドでテーブルを作成し、ダミーデータを投入します。

```
sail artisan migrate:fresh --seed
```

DB接続時にAccess deniedなどのエラーが表示される場合は、以前作成されたDockerボリュームのDB情報が残っている可能性があります。

以下のコマンドを実行するとDBデータがすべて削除されるため、必要なデータがないことを確認してから実行してください。

```
sail down -v
sail up -d
```

MySQLコンテナが起動するまで30秒ほど待ってから、もう一度実行します。

```
sail artisan migrate:fresh --seed
```

8. フロントエンドのセットアップ

```
sail npm install
sail npm run dev
```

npm run devは開発中は起動したままにしてください。

9. アプリケーションへのアクセス

ブラウザで http://localhost にアクセスします。

## 動作確認用ユーザー

migrate:fresh --seed実行後、次のユーザーでログインできます。主要な読書計画の確認用データは山田太郎に集約しています。

| 名前     | メールアドレス        | パスワード |
| -------- | --------------------- | ---------- |
| 山田太郎 | yamada@example.com    | password   |
| 鈴木花子 | suzuki@example.com    | password   |
| 田中一郎 | tanaka@example.com    | password   |
| 佐藤美咲 | sato@example.com      | password   |
| 高橋健太 | takahashi@example.com | password   |

## テスト実行

```
sail artisan test
```

カバレッジ付きで実行する場合:

```
sail artisan test --coverage
```

## 機能一覧

- ユーザー認証（登録、ログイン、ログアウト）

- 書籍CRUD（一覧、詳細、登録、編集、削除）

- 書籍のキーワード検索、ジャンル絞り込み、並び替え

- ISBNによるGoogle Books API検索と登録フォームへの自動入力

- ジャンルCRUD

- レビューCRUD

- レビューへのいいね追加・解除

- 書籍のお気に入り追加・解除

- 書籍ランキング

- マイ読書レポート

- 読書計画の登録、期日変更、読了、削除、状態絞り込み

- 読書計画の自動失効

- 読書計画のリマインダー通知

- 通知一覧と既読化

- Sanctum認証付き公開API

## APIエンドポイント一覧

読み取り系APIは認証不要です。書き込み系APIはLaravel SanctumのBearerトークン認証が必要です。全エンドポイントは/api/v1プレフィックス配下に定義されています

| HTTPメソッド | URL                       | 認証             | 概要                                             |
| ------------ | ------------------------- | ---------------- | ------------------------------------------------ |
| GET          | `/api/v1/books`           | 不要             | 書籍一覧（検索・絞り込み・ページネーション付き） |
| GET          | `/api/v1/books/{book}`    | 不要             | 書籍詳細（ジャンル・レビュー含む）               |
| POST         | `/api/v1/books`           | Sanctum          | 書籍を新規登録                                   |
| PUT          | `/api/v1/books/{contact}` | Sanctum + 所有者 | 書籍を更新                                       |
| DELETE       | `/api/v1/books/{contact}` | Sanctum + 所有者 | 書籍を削除                                       |

## Sanctumトークンの発行

動作確認用のAPIトークンはTinkerから発行します。

```
sail artisan tinker

$user = App\Models\User::where('email', 'yamada@example.com')->firstOrFail();
$token = $user->createToken('postman')->plainTextToken;
$token;
```

Postmanなどから書き込み系APIを利用するときは、次のヘッダーを設定します。

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer 発行したトークン
```

## 日次バッチ処理

- 期限を過ぎた読書中の計画を期限切れへ更新

```
sail artisan update:reading-plan-status
```

- 条件を満たす読書計画へリマインダー通知を保存

```
sail artisan notify:reading-plan
```
