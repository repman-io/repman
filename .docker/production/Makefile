# Default target
.DEFAULT_GOAL := web

_prepare_application:
	# run post-install hooks as dependencies may depend on this
	php composer.phar install -n --prefer-dist --no-scripts -o --no-dev

# MAIN TARGETS
web: _prepare_application
	sudo nginx
	sudo -E LD_PRELOAD=/usr/lib/preloadable_libiconv.so php-fpm

cron: _prepare_application
	/var/www/html/config/crons/${CRON_FILE}

pre-deploy: _prepare_application
	php composer.phar pre-deploy --timeout 1800

worker: _prepare_application
	sudo -E LD_PRELOAD=/usr/lib/preloadable_libiconv.so supervisord -c /etc/supervisord.conf

# ADDITIONAL TARGETS
shell:
	bash
