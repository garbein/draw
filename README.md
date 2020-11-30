# Draw

## Demo

- [http://api.cloudci.com/activity/index.html](http://api.cloudci.com/activity/index.html)

## 安装

### 下载源码

```shell
mkdir -p /data/www/
cd /data/www/
git clone https://github.com/garbein/draw.git
cd draw
```

### 创建数据库导入表结构

* 登录mysql

```shell
mysql -uroot -p
```

* 导入数据库

```mysql
source /data/www/draw/sql/schema.sql
```

## 修改配置

* 配置mysql

```shell
vim /data/www/draw/config/db.php
```

* 配置redis

```shell
vim /data/www/draw/config/redis.php
```

## 运行

```shell
./yii serve
```

## 访问

- [http://localhost:8080/activity/index.html](http://localhost:8080/activity/index.html)