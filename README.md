# Certify LMS

マルチ資格対応の資格学習プラットフォームです。受講生は資格ごとの教材で学習し、演習問題・模擬試験で理解度を確かめながら、コーチの面談サポートを受けて資格取得を目指せます。

> プロジェクト構造・ドメインモデル・コードの読み進め方は [ONBOARDING.md](./ONBOARDING.md) を参照してください。

## 主な機能

| ロール            | 機能                                                                                                                                                        |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 受講生（student） | 教材閲覧 / 演習問題・苦手分野ドリル / 模擬試験（分野別ヒートマップ・合格可能性スコア）/ 面談予約 / チャット / 学習時間・進捗・ストリーク管理 / 修了証の受領 |
| コーチ（coach）   | 教材・演習問題・模試の管理 / 担当受講生の進捗フォロー / 面談対応・面談メモ / チャット                                                                       |
| 管理者（admin）   | ユーザー招待・管理 / 資格・資格分類マスタ管理 / 資格へのコーチ割当 / 面談回数の付与 / 全体ダッシュボード                                                    |

## 動作環境

- Docker Desktop / Docker Compose
- 開発環境は Laravel Sail で構築します（PHP コンテナ・MySQL・Mailpit・phpMyAdmin を起動）

## 環境構築手順

### 1. リポジトリの clone

```bash
git clone <このリポジトリの URL>
cd <リポジトリ名>
```

### 2. 環境変数ファイルの作成

```bash
cp .env.example .env
```

`.env.example` は Sail 向けに設定済みのため、コピーするだけでローカル開発を始められます（外部サービス連携のキーは後述）。

### 3. 依存パッケージのインストール（初回のみ）

`vendor/` がまだ無いため、初回のみ Docker 経由で Composer を実行します。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Sail エイリアスの設定（推奨）

```bash
alias sail='./vendor/bin/sail'
```

以降のコマンドはこのエイリアス前提で記載します（未設定の場合は `./vendor/bin/sail` に読み替えてください）。

### 5. コンテナの起動

```bash
sail up -d
```

### 6. アプリケーションの初期化

```bash
sail artisan key:generate
sail artisan storage:link
sail artisan migrate:fresh --seed
```

`storage:link` は教材画像・プロフィール画像の配信に必要です。`migrate:fresh --seed` でテーブル作成とデモデータ投入が行われます（いつでも再実行してデータを初期状態に戻せます）。

### 7. フロントエンドのビルド

```bash
sail npm install
sail npm run build
```

Blade / CSS / JS を編集しながら開発する場合は、`build` の代わりに `sail npm run dev` を起動したままにしてください（Vite のホットリロードが効きます）。

### 8. 動作確認

http://localhost:8000 にアクセスし、下記の[ログインアカウント](#ログインアカウント)でログインできればセットアップ完了です。

## 開発環境 URL

| 用途                  | URL                   |
| --------------------- | --------------------- |
| アプリケーション      | http://localhost:8000 |
| phpMyAdmin（DB 確認） | http://localhost:8080 |
| Mailpit（メール確認） | http://localhost:8025 |

アプリケーションが送信するメール（招待メールなど）はすべて Mailpit に届きます。実際のメールは送信されません。

## ログインアカウント

`migrate:fresh --seed` 後、以下の固定アカウントが使えます（パスワードはすべて `password`）。

| ロール | メールアドレス           | 備考                                             |
| ------ | ------------------------ | ------------------------------------------------ |
| 管理者 | admin@certify-lms.test   | 全機能にアクセス可能                             |
| コーチ | coach@certify-lms.test   | IT 系資格の担当                                  |
| コーチ | coach2@certify-lms.test  | ビジネス系資格の担当                             |
| 受講生 | student@certify-lms.test | 受講中の資格・学習履歴・面談などのデモデータ付き |

このほか、ライフサイクル（招待中 / 受講中 / 卒業 / 退会）を網羅したデモユーザーが投入されます。

> 本サービスは**招待制**です。公開の会員登録画面はありません。新規ユーザーを作るには、管理者でログイン → ユーザー管理から招待 → Mailpit で招待メールの URL を開く → オンボーディング登録、という流れになります。

## テスト

```bash
sail artisan test                  # 全テスト実行
sail artisan test --filter=Xxx    # クラス名・メソッド名で絞り込み
```

## コード整形

Laravel Pint を使用しています。コミット前に実行してください。

```bash
sail bin pint --dirty    # 変更ファイルのみ整形
sail bin pint --test     # 整形漏れの確認（CI 相当のチェック）
```

## キューワーカー

通知・メール配信はデータベースキューを使用して非同期で処理します。

ローカル環境では、Sail のコンテナ起動後に別ターミナルでキューワーカーを起動してください。

```bash
sail artisan queue:work
```

キュージョブは最大3回まで実行され、失敗時は10秒、30秒と段階的に待機して再試行します。上限まで失敗したジョブは `failed_jobs` テーブルに記録されます。

失敗したジョブは次のコマンドで確認できます。

```bash
sail artisan queue:failed
```

失敗したジョブを再投入する場合は、対象の ID を指定して実行します。

```bash
sail artisan queue:retry <id>
```

すべての失敗ジョブを再投入する場合は、次のコマンドを実行します。

```bash
sail artisan queue:retry all
```

## 使用技術

- PHP 8.5 / Laravel 10
- MySQL 8.4
- Laravel Fortify（認証）/ Laravel Sanctum（API 認証）
- Blade + Tailwind CSS + Vite（JavaScript は素の JS、フレームワーク不使用）
- PHPUnit / Laravel Pint
- league/commonmark（教材本文の Markdown レンダリング）
- Pusher（チャットのリアルタイム配信）
- Stripe（追加面談回数の決済）
- Docker（Laravel Sail）

## 環境変数

`.env.example` をコピーするだけで、基本機能をローカルで動作確認できます（メールは Mailpit に配信されます）。外部サービス連携を利用する場合は、各サービスの認証情報を設定してください。

- `PUSHER_*` — チャットのリアルタイム配信に使用します。有効にする場合は Pusher のキーを取得して設定し、`BROADCAST_DRIVER=pusher` に変更してください。未設定（既定の `BROADCAST_DRIVER=log`）でもメッセージの送受信自体は動作し、相手画面へのリアルタイム反映のみ行われません
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI` — コーチの Google Calendar 連携に使用します。Google Cloud Console で Calendar API を有効化し、OAuth 2.0 クライアントを作成して設定してください。ローカル環境ではリダイレクト URI に `http://localhost:8000/settings/google-calendar/callback` を設定します
- Google Calendar のアクセストークン・リフレッシュトークンは、開発環境ではデータベースに保存しています。本番環境では、認証情報を暗号化して保存することを推奨します
- `STRIPE_SECRET` — Stripe Checkout セッションの作成に使用します。Stripe の Sandbox 環境で発行したシークレットキーを設定してください
- `STRIPE_WEBHOOK_SECRET` — Stripe Webhook の署名検証に使用します。ローカル環境では Stripe CLI の `stripe listen` 実行時に発行される Webhook signing secret を設定してください

新しい環境変数やセットアップ手順を追加した場合は、`.env.example` と本 README に追記し、チームの誰でも環境を再現できる状態を保ってください。

## Stripe Webhook のローカル動作確認

追加面談回数の購入では Stripe Checkout を使用しています。

ローカル環境で決済完了後の Webhook を受信するには、Stripe CLI を使用して Stripe からローカルアプリケーションへイベントを転送します。

### 1. Stripe の Sandbox 用シークレットキーを設定

Stripe の Sandbox 環境でシークレットキーを取得し、`.env` の `STRIPE_SECRET` に設定します。

```env
STRIPE_SECRET=
```

実際のシークレットキーは `.env` のみに設定し、リポジトリにはコミットしないでください。

### 2. Stripe CLI のインストール

Stripe CLI が未インストールの場合はインストールします。

macOS では Homebrew を使用できます。

```bash
brew install stripe/stripe-cli/stripe
```

インストール後、バージョンを確認します。

```bash
stripe --version
```

### 3. Stripe CLI にログイン

```bash
stripe login
```

ブラウザに表示される認証画面からログインし、このプロジェクトで使用する Stripe の Sandbox 環境を選択します。

### 4. Webhook の転送を開始

以下のコマンドを実行し、`checkout.session.completed` イベントをローカルの Webhook エンドポイントへ転送します。

```bash
stripe listen \
  --events checkout.session.completed \
  --forward-to http://localhost:8000/webhooks/stripe
```

Stripe CLI に次のような Webhook signing secret が表示されます。

```text
whsec_...
```

表示された値を `.env` の `STRIPE_WEBHOOK_SECRET` に設定します。

```env
STRIPE_WEBHOOK_SECRET=
```

設定後、Laravel の設定キャッシュをクリアします。

```bash
sail artisan config:clear
```

`stripe listen` は Webhook の動作確認中、そのまま起動しておいてください。

### 5. Stripe Checkout で決済を実行

アプリケーションへ受講中の受講生アカウントでログインし、追加面談回数の購入画面から公開中の面談パックを選択します。

Stripe Checkout に遷移したら、Stripe Sandbox のテスト用カード情報を使用して決済を完了します。

### 6. Webhook の受信を確認

決済が完了すると、Stripe CLI に `checkout.session.completed` イベントとローカル Webhook エンドポイントへのレスポンスが表示されます。

`POST http://localhost:8000/webhooks/stripe` が `200` を返していることを確認してください。

Webhook が正常に処理されると、対象の決済が完了状態になり、購入した面談回数が受講生の利用可能回数へ反映されます。

あわせて以下を確認してください。

- 決済完了画面に購入内容が表示される
- 購入した面談回数が利用可能回数へ加算される
- 面談回数の履歴に購入分が表示される
- 同じ Webhook が再送されても面談回数が重複して加算されない

> Stripe のシークレットキーおよび Webhook signing secret は `.env` のみに設定し、`.env.example` や README、Git の管理対象には実際の値を記載しないでください。
