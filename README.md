# 居酒屋検索Bot

## require 

- phalcon 3.0.1
- redis
- php5.6
- nginx
- php-fpm

## Worker

```
nohup sudo -u apache QUEUE=events APP_INCLUDE=/var/www/linebot/bin/cli.php /usr/bin/php /var/www/linebot/vendor/bin/resque >> /var/log/workers/workers.log 2>&1 &
```
