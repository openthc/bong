#!/bin/bash
#
# Does make like things
#

set -o errexit
set -o errtrace
set -o nounset
set -o pipefail

BIN_SELF=$(readlink -f "$0")
APP_ROOT=$(dirname "$BIN_SELF")

action="${1:-}"
shift

case "$action" in
# Install or Update the System
install|update)

	composer install --no-ansi --no-dev --no-progress --quiet --classmap-authoritative

	npm install

	./make.sh vendor-web

	;;

# Get the CSS and JS Assets
vendor-web)

	. vendor/openthc/common/lib/lib.sh

	copy_bootstrap
	copy_fontawesome
	copy_jquery


	# lodash
	mkdir -p webroot/vendor/lodash/
	cp node_modules/lodash/lodash.min.js webroot/vendor/lodash/

	# htmx
	mkdir -p webroot/vendor/htmx
	cp node_modules/htmx.org/dist/htmx.min.js webroot/vendor/htmx/

	;;

# Help, the default target
*)

	echo
	echo "You must supply a make command"
	echo
	awk '/^# [A-Z].+/ { h=$0 }; /^[a-z]+.+\)/ { printf " \033[0;49;31m%-15s\033[0m%s\n", gensub(/\)$/, "", 1, $$1), h }' "$BIN_SELF" |sort
	echo

	;;

esac
