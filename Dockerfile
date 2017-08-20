FROM php:7.0
MAINTAINER Samuel Terburg <sterburg@hoolia.eu>

CMD /usr/libexec/s2i/run

ENV WORDPRESS_VERSION 4.7

USER root

RUN mv $STI_SCRIPTS_PATH/run      $STI_SCRIPTS_PATH/run-base \
 && mv $STI_SCRIPTS_PATH/assemble $STI_SCRIPTS_PATH/assemble-base

COPY contrib/*.php* /opt/app-root/src/
COPY contrib/*.crt  /etc/pki/ca-trust/source/anchors/
COPY s2i/bin/*      $STI_SCRIPTS_PATH/

RUN update-ca-trust

RUN { \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=8'; \
      echo 'opcache.max_accelerated_files=4000'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'opcache.fast_shutdown=1'; \
      echo 'opcache.enable_cli=1'; \
    } > /etc/opt/rh/rh-php70/php.d/11-opcache-wordpress.ini

RUN curl -vo /tmp/wordpress.tar.gz -SL https://wordpress.org/wordpress-${WORDPRESS_VERSION}.tar.gz 
RUN tar -xzf /tmp/wordpress.tar.gz --strip-components=1 -C .
RUN rm -f /tmp/wordpress.tar.gz
RUN mv ./wp-content ./wp-content-install
RUN chmod -R u=rwX,go=rX ./*

VOLUME /opt/app-root/src/wp-content

USER 1001
