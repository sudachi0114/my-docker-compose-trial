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

```info.php
<?php phpinfo(); ?>
```


* 最後に nginx の設定を書きます。

これを書かないと、localhost:8080/info.php にアクセスしたときに
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

```php.ini
[Date]
date.timezone = "Asia/Tokyo"
[mbstring]
mbstring.internal_encoding = "UTF-8"
mbstring.language = "Japanese"
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


## Links

* [Docker ComposeでNginxとphpを連携する](https://zukucode.com/2019/06/docker-compose-nginx-php.html)