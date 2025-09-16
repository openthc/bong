#!/bin/bash
#
#

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail

# printenv | sort


#
# PHP Debugger
OPENTHC_DEBUG=${OPENTHC_DEBUG:-"false"}
if [ "$OPENTHC_DEBUG" == "true" ]
then
	echo "DEBUG ENABLED"
	phpenmod xdebug
fi


#
# Start Apache
exec /usr/sbin/apache2 -DFOREGROUND
