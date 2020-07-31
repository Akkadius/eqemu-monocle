FROM php:7.4.8-zts-buster

RUN apt-get update && apt-get install -y \
	vim \
	wget \
	zip \
	unzip \
	git \
	&& rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
