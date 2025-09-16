#
# OpenTHC BONG Docker Image
#

FROM openthc/web:latest

#
# Install
COPY ./ /opt/openthc/bong
RUN cd /opt/openthc/bong \
	&& /opt/openthc/bong/make.sh


#
# Copy
COPY ./docker/apache2.conf         /etc/apache2/sites-enabled/bong.conf
COPY ./docker/init.sh              /init.sh
# COPY ./docker/cre-openthc.yaml   /opt/openthc/app/etc/cre/openthc.yaml
# COPY ./docker/first-run.php      /openthc-config-init.php
# COPY ./docker/xdebug.ini         /etc/php/7.4/mods-available/xdebug.ini


#
# Ports
EXPOSE 80


#
# Volumes
VOLUME [ "/opt/openthc/gfs" ]


#
# Environment
ENV OPENTHC_SERVER_NAME="bong.docker.openthc.me"


ENTRYPOINT [ "/init.sh" ]
