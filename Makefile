#
# OpenTHC BONG Makefile
#

SHELL = /bin/bash
.PHONY: help

export APP_ROOT := $(realpath $(@D) )

#
# Help, the default target
help:
	@echo
	@echo "You must supply a make command, try 'make system-install'"
	@echo
	@grep -ozP "#\n#.*\n[\w\-]+:" $(MAKEFILE_LIST) \
		| awk '/[a-zA-Z0-9_-]+:/ { printf "  \033[0;49;32m%-20s\033[0m%s\n", $$1, gensub(/^# /, "", 1, x) }; { x=$$0 }' \
		| sort
	@echo


#
# PHP Composer
composer:
	composer update --no-dev -a


#
# NPM Update
npm:
	npm update


#
# Install Live Environment
install: composer npm

	mkdir -p webroot/js webroot/css/

	cp node_modules/jquery/dist/jquery.min.js webroot/js/

	cp node_modules/jquery-ui/dist/themes/base/jquery-ui.min.css webroot/css/
	cp node_modules/jquery-ui/dist/jquery-ui.min.js webroot/js/

	cp node_modules/bootstrap/dist/css/bootstrap.min.css webroot/css/
	cp node_modules/bootstrap/dist/js/bootstrap.bundle.min.js webroot/js/

	cp node_modules/htmx.org/dist/htmx.min.js webroot/js/
