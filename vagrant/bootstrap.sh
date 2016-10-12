#!/usr/bin/env bash
export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y git curl
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

apt-get install -y make unzip php-dev php-curl beanstalkd

mysql -e "CREATE DATABASE wakeup"
mysql -e "grant all privileges on wakeup.* to 'vagrant'@'localhost' identified by 'vagrant'"
