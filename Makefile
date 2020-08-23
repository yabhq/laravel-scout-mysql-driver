all: build install test

test:
	docker run -it -v $(shell pwd):/app laravel-scout-mysql-driver vendor/bin/phpunit

install:
	docker run -it -v $(shell pwd):/app laravel-scout-mysql-driver composer install

bash:
	docker run -it -v $(shell pwd):/app -it laravel-scout-mysql-driver bash

build:
	docker build -t laravel-scout-mysql-driver:latest .
