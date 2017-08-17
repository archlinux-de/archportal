.PHONY: all init start stop restart clean rebuild composer-update update-data bash test

APP-RUN=docker-compose run --rm -u $$(id -u) app
DB-RUN=docker-compose run --rm db

all: init

init: start
	${DB-RUN} mysqladmin -uroot create archportal
	${APP-RUN} config/ImportSchema.php
	${APP-RUN} config/UpdateCountries.php
	${MAKE} update-data

update-data:
	${APP-RUN} cronjobs/UpdateMirrors.php
	${APP-RUN} cronjobs/UpdateNews.php
	${APP-RUN} cronjobs/UpdateReleases.php
	${APP-RUN} cronjobs/UpdatePackages.php
	${APP-RUN} cronjobs/UpdatePkgstats.php

start: vendor
	docker-compose up -d
	${DB-RUN} mysqladmin -uroot --wait=10 ping

stop:
	docker-compose stop

restart:
	${MAKE} stop
	${MAKE} start

clean:
	docker-compose down -v
	git clean -fdqx

rebuild: clean
	docker-compose build --no-cache --pull
	${MAKE}

composer-update:
	${APP-RUN} composer update

composer.lock: composer.json
	${APP-RUN} composer update nothing

vendor: composer.lock
	mkdir -p ~/.composer/cache
	${APP-RUN} composer install

shell:
	${APP-RUN} bash

test:
	${APP-RUN} vendor/bin/phpunit
