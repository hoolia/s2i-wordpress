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
RUN chmod -R a+rwX /etc/opt/rh/rh-php70/php.d/
RUN chmod -R u=rwX,go=rX ./*

RUN update-ca-trust

## SMTP ##
RUN yum install -y http://epel.mirror.nucleus.be//7Server/x86_64/e/epel-release-7-10.noarch.rpm \
 && yum install -y ssmtp nss_wrapper \
 && yum clean all -y \
 && chmod 777 /etc/ssmtp \
 && chmod g-s /usr/sbin/ssmtp
ADD contrib/ssmtp.conf /etc/ssmtp/ssmtp.conf

## MemCached ##
RUN yum install -y \
                --enablerepo rhel-server-rhscl-7-rpms \
                --enablerepo rhel-7-server-optional-rpms \
                rh-php70-php-devel \
                libmemcached-devel \
                libmemcached \
 && pecl install memcached
ADD contrib/41-memcached.ini /etc/opt/rh/rh-php70/php.d/41-memcached.ini


## OpCache ##
Add contrib/11-opcache-wordpress.ini /etc/opt/rh/rh-php70/php.d/11-opcache-wordpress.ini

USER 1001

RUN curl -vo /tmp/wordpress.tar.gz -SL https://wordpress.org/wordpress-${WORDPRESS_VERSION}.tar.gz 
RUN tar -xzf /tmp/wordpress.tar.gz --strip-components=1 -C .
RUN rm -f /tmp/wordpress.tar.gz
RUN mv ./wp-content ./wp-content-install
RUN chmod -R u=rwX,go=rX ./* || true

VOLUME /opt/app-root/src/wp-content
