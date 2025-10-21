#
# OpenTHC BONG Docker Image
#

FROM openthc/web:latest

#
# Environment
ENV OPENTHC_SERVICE="bong"
ENV OPENTHC_SERVER_NAME="bong.docker.openthc.org"

#
# Install
COPY --exclude=docker/ ./ /opt/openthc/bong
RUN /opt/openthc/bong/make.sh

#
# Configure
COPY ./docker/apache2.conf  /etc/apache2/sites-enabled/000-default.conf
COPY ./docker/xdebug.ini    /etc/php/7.4/mods-available/xdebug.ini
COPY ./docker/init.sh       /init.sh
COPY ./docker/init.php      /opt/openthc/bong/docker/init.php

#
# Volumes
VOLUME [ "/opt/openthc/gfs" ]

#
# Ports
EXPOSE 80

#
# Go!
CMD [ "/init.sh" ]
