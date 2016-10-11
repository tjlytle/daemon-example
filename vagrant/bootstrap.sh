#!/usr/bin/env bash
export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y git curl
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

apt-get install -y make php-dev php-curl beanstalkd unzip
