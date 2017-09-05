FROM centos/php-70-centos7:latest
MAINTAINER Samuel Terburg <sterburg@hoolia.eu>

CMD /usr/libexec/s2i/run

ENV WORDPRESS_VERSION ${WORDPRESS_VERSION:-4.8.1}

USER root

RUN mv $STI_SCRIPTS_PATH/run      $STI_SCRIPTS_PATH/run-base \
 && mv $STI_SCRIPTS_PATH/assemble $STI_SCRIPTS_PATH/assemble-base

COPY contrib/*.php* /opt/app-root/src/
COPY contrib/*.crt  /etc/pki/ca-trust/source/anchors/
COPY s2i/bin/*      $STI_SCRIPTS_PATH/

RUN update-ca-trust

RUN yum search memcached
RUN yum --enablerepo=centos-sclo-sclo-testing search memcached
RUN yum repos --list
RUN yum -y install sclo-php70-php-pecl-memcached && \
    yum clean all -y

RUN { \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=8'; \
      echo 'opcache.max_accelerated_files=4000'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'opcache.fast_shutdown=1'; \
      echo 'opcache.enable_cli=1'; \
    } > /etc/opt/rh/rh-php70/php.d/11-opcache-wordpress.ini

USER 1001

RUN curl -vo /tmp/wordpress.tar.gz -SL https://wordpress.org/wordpress-${WORDPRESS_VERSION}.tar.gz 
RUN tar -xzf /tmp/wordpress.tar.gz --strip-components=1 -C .
RUN rm -f /tmp/wordpress.tar.gz
RUN mv ./wp-content ./wp-content-install
RUN chmod -R u=rwX,go=rX ./*

VOLUME /opt/app-root/src/wp-content
