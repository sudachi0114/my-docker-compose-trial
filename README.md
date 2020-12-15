## Nginx で web サーバを立てる

まずは nginx のイメージから web サーバの役割をするコンテナをサービスとして定義する。
定義には `docker-compose.yml` というファイルを用いる。
これをプロジェクトルートに置き、`docker-compose` コマンドを実行することで、この定義に従ったサービス (たち) が立ち上がる。

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

表示テスト用に html を用意しておく。

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
このファイルは、プロジェクトルートに配置する。
プロジェクトルートを nginx のドキュメントルートである `/usr/share/nginx/html` にマウントすることで
url のルートにアクセスした時に、この html が表示される。

* execute docker-compose

```sh
# start service
docker-compose up -d

# stop service
docker-compose stop

# stop and remove container 
docker-compose down
```

Then access to http://localhost:8080 

#### TIPS 🖇: use norimal docker

なお、ここまででやっていることは普通の `docker` コマンドでできることと変わらない。

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