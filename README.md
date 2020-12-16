# docker-compose の練習とまとめ

個人的に docker-compose を自分で学びたい！
ということで、1から作ってみます。

特にコンテナ同士の関連 (アプリケーションとDBの接続) とか
yaml ファイルの記述とかに注力してみました。


## Nginx で web サーバを立てる

* まずは nginx のイメージから web サーバの役割をするコンテナをサービスとして定義します。

定義には `docker-compose.yml` というファイルを用います。
これをプロジェクトルートに置き、`docker-compose` コマンドを実行することで、この定義に従ったサービス (たち) が立ち上がります。


```docker-compose.yml
version: '3'

services: 
  web:
    image: nginx
    volumes:
      - .:/usr/share/nginx/html
    ports: 
      - '8080:80'
```

* 表示テスト用に html を用意しておきます。

ローカル (ホスト? コンテナではなく、手元にある本体のPCという意味) にファイルを作成し、コンテナ作成時にマウントします。

基本 docker 関係で使うソースファイルはこの手法で利用することが多い、という印象です。
このプロジェクトでも、例にもれることなく、この方法を後で多用します。

```index.html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>my-docker-compose trial</title>
</head>
<body>
    <h1>Hello from Nginx!</h1>
    <p>This is a page of my docker compose trial.</p>
</body>
</html>
```

このファイルは、プロジェクトルートに配置します。
プロジェクトルートを nginx のドキュメントルートである `/usr/share/nginx/html` にマウントすることで
url のルートにアクセスした時に、この html が表示されるようになります。

* execute docker-compose

```sh
# start service
docker-compose up -d

# show logs
docker-compose logs -f web

# stop service
docker-compose stop

# stop and remove container 
docker-compose down
```

Then access to http://localhost:8080 


#### TIPS 🖇: use norimal docker

なお、ここまででやっていることは普通の `docker` コマンドでできます。
やっていることは以下と変わりません。

```sh
# start container 
docker run -d --rm \
    --name mynginx \
    -p 8080:80 \
    -v `pwd`:/usr/share/nginx/html \
    nginx

# stop container (and automaticaly remove)
docker stop mynginx 
```

## サービスに php アプリケーションを追加

* `docker-compose.yml` に `app` というサービスを追加します。

`app` は php の docker image から作られたコンテナです。

```docker-compose.yml
version: '3'

services: 
  web:
    image: nginx
    volumes:
      - ./misc/nginx/conf.d/default.conf:/etc/nginx/conf.d/default.conf
      - .:/var/www/html
    ports: 
      - '8080:80'
    depends_on: 
      - app

  app:
    image: php:7.2-fpm
    volumes:
      - .:/var/www/html
```

* 次に、php アプリケーションを用意します。
「利用している php のバージョンを表示する」という簡単なプログラム (?) です。
`info.php` という名前で用意してみました。

```info.php
<?php phpinfo(); ?>
```


* 最後に nginx の設定を書きます。

これを書かないと、http://localhost:8080/info.php にアクセスしたときに
php ファイルがブラウザで表示されず、ダウンロードされてしまいます。

```default.conf
server {
  root  /var/www/html;
  index index.php index.html;

  location / {
      try_files $uri $uri/ /index.php$is_args$args;
  }

  location ~ \.php$ {
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass  app:9000;
    fastcgi_index index.php;

    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param PATH_INFO $fastcgi_path_info;
  }
}
```

nginx のドキュメントルートも `/usr/share/nginx/html` から `/var/www/html` に変えます。
後者のディレクトリは、`apache server` などのドキュメントルートとしてポピュラーな場所だと思います。

*  php の設定ファイルも書きます

php のタイムゾーンや、文字コードに関する設定のファイルを記述します。

```php.ini
[Date]
date.timezone = "Asia/Tokyo"
[mbstring]
mbstring.internal_encoding = "UTF-8"
mbstring.language = "Japanese"
```

この設定ファイルもコンテナにマウントするように `docker-compose.yml` を編集します。

```docker-compose.yml
version: '3'

services: 
  web:
    image: nginx
    volumes:
      - ./misc/nginx/conf.d/default.conf:/etc/nginx/conf.d/default.conf
      - .:/var/www/html
    ports: 
      - '8080:80'
    depends_on: 
      - app

  app:
    image: php:7.2-fpm
    volumes:
      - .:/var/www/html
      - ./app/php.ini:/usr/local/etc/php/php.ini
```


* 最終的なプロジェクト構成

```
.
├── README.md
├── app
│   └── php.ini
├── docker-compose.yml
├── index.html
├── info.php
└── misc/nginx/conf.d
    └── default.conf
```

## DB (MySQL) の設定と接続

docker-compose でアプリケーションとDBを立てて、それらを接続します。

```
# イメージ図

[user] :8080 <---> :80 [web: nginx] <---> [app: php application] :3306 <---> :3306 [db: MySQL]

```


* まずは DB (今回は `MySQL` を使います ) をサービスとして定義します。

サービス名前は素直に `db` にしました。

設定ファイルが増えてきたので `services` というディレクトリを作って、その下にまとめましたが、
`services` というディレクトリ名である必要はなく、~~私のネーミングセンスのなさ~~ 見やすさのための命名です。

```docker-compose.yml
version: '3'

services: 
  web:
    image: nginx
    volumes:
      - ./misc/nginx/conf.d/default.conf:/etc/nginx/conf.d/default.conf
      - .:/var/www/html
    ports: 
      - '8080:80'
    depends_on: 
      - app

  app:
    build: ./services/app
    volumes:
      - ./services/app/php.ini:/usr/local/etc/php/php.ini
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:5.7
    environment: 
      - MYSQL_ROOT_PASSWORD
      - MYSQL_DATABASE
      - MYSQL_USER
      - MYSQL_PASSWORD
    ports: 
      - '3306:3306'
    volumes: 
      - ./services/db/data:/var/lib/mysql
      - ./services/db/my.cnf:/etc/mysql/conf.d/my.cnf
```

`db` の `environment` に関してですが、DBのコンテナの作成時に、
サービスで使うユーザを作ったり、そのパスワードを設定したり、アプリケーション用のデータベースを作ったりするための定義部分です。

環境変数として持たせる形でセットしますが、
安全面や利便性などを考え `docker-compose.override.yml` というファイルを作り、そこに個人の設定を記入すると良いと思います。

```docker-compose.override.yml
version: '3'

services: 
  db:
    environment: 
      - MYSQL_ROOT_PASSWORD=toor
      - MYSQL_DATABASE=test_db
      - MYSQL_USER=sudachi
      - MYSQL_PASSWORD=password
```


今回は `docker-compose.override.sample.yml` に例を作りました。
以下のようにすると使えると思います。

```sh
cp -p docker-compose.override.sample.yml docker-compose.override.yml

docker-compose up -d
```


その名の通り、`docker-compose.yml` に `docker-compose.override.yml` の内容が上書きされる形で設定されます。

オーバーライドする設定ファイルを使うことで、各user毎に設定内容を変えることができるというメリットがあります。
(各自の環境におけるオーバーライドの設定は `.gitignore` で無視することによって、リポジトリには上げない)

逆に、`override.yml` の内容を自分の環境に揃えることを他の開発者の方々にきちんと共有する必要があるということにも注意したいですね。


さて、MySQL に関しては、最後に設定ファイルを追加して定義を終了します。

```sh
touch ./services/db/my.cnf
touch ./services/db/.gitignore
echo '*' >> ./services/db/.gitignore
```

`my.cnf` は MySQL の設定ファイルのようです。
ここまでの内容では特に設定する事柄はないので、ファイルの中身は空のままでOKです。


* php アプリケーションを追加します。

DB に接続し、MySQL のバージョンを求めて、画面に表示するだけのアプリケーションを作ります。

```connect.php
<?php
    try {
        $dsn = 'mysql:host=db;dbname=test_db;';
        $db = new PDO($dsn, 'sudachi', 'password');  // FIXME:

        $sql = 'SELECT version();';
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        var_dump($result);
    } catch (PDOExeption $e) {
        echo $e->getMessage();
        exit;
    }
?>
```

access to http://localhost:8080/connect.php


さて、これで webサーバ、phpアプリケーション、DB を持つサービスの環境が整いました！


## Links

* [Docker ComposeでNginxとphpを連携する](https://zukucode.com/2019/06/docker-compose-nginx-php.html)
* [Docker Composeでphpでmysqlにアクセスする](https://zukucode.com/2019/06/docker-compose-mysql.html)
* [docker-composeでbuildができなくなる問題の解決策メモ (credHelpersのディレクティブ由来の不具合)](https://marsquai.com/7d9c0dd5-2fe1-4725-8cca-e766b4682aea/da1900c3-255d-489a-9201-f02a639fe4a5/71e724cd-212c-4301-b6d9-378312479f34/)
* [docker-compose.override.yml](https://docs.docker.jp/compose/extends.html)