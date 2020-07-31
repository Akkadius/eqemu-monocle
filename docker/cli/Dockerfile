FROM php:7.4.8-zts-buster

#####################################
# Install modules
#####################################
RUN apt-get update && apt-get install -y --no-install-recommends \
	$PHPIZE_DEPS \
	bash \
	bc \
	cron \
	git \
	htop \
	make \
	libcurl4-gnutls-dev \
	libpcre3-dev \
	libgcrypt-dev \
	libmemcached-dev \
	libxml2-dev \
	libxslt-dev \
	libssl-dev \
	sudo \
	supervisor \
	net-tools \
	tzdata \
	procps \
	zlib1g-dev \
	libzip-dev \
	zip \
	unzip && \
	pecl channel-update pecl.php.net && \
	pecl install -o -f igbinary && \
	pecl install -o -f msgpack && \
	pecl install -o -f redis && \
	pecl install -o -f memcached && \
	pecl install -o -f oauth && \
	docker-php-ext-configure intl && \
	docker-php-ext-install -j$(nproc) \
		calendar \
		curl \
		exif \
		gettext \
		intl \
		mysqli \
		opcache \
		pcntl \
		pdo_mysql \
		shmop \
		soap \
		sockets \
		sysvmsg \
		sysvsem \
		sysvshm \
		xsl \
		zip && \
	docker-php-ext-enable \
		igbinary \
		memcached \
		msgpack \
		oauth \
		redis \
	&& rm -rf /tmp/pear \
	&& apt-get remove -y $PHPIZE_DEPS && rm -rf /var/lib/apt/lists/*

#####################################
# add docker user
#####################################
ARG PUID=1000
ENV PUID ${PUID}
ARG PGID=1000
ENV PGID ${PGID}

RUN apt-get update -yqq && \
	groupadd -g ${PGID} docker && \
	useradd -u ${PUID} -g docker -m docker -G docker && \
	usermod -p "*" docker && \
	echo 'docker  ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

#############################################
# default shell bash
#############################################
RUN chsh -s /bin/bash docker

#####################################
# perms
#####################################
RUN mkdir -p /var/www/code/ && chgrp -R www-data /var/www/code \
	&& chmod 775 /var/www/code && chown docker:docker /var/www/code/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

USER docker

RUN composer global require hirak/prestissimo

ENV PS1 '\[\e]0;\w\a\]\n\[\e[32m\]\u@\h \[\e[33m\]\w\[\e[0m\]\n\$ '

WORKDIR /var/www/code
