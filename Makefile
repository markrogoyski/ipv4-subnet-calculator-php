.PHONY : all lint tests style phpstan phpmd psalm composer-unused composer-require-checker composer-audit coverage

# Composer command: composer.phar run through php if present, otherwise composer on PATH. Override with
# COMPOSER_BIN=... (not COMPOSER, which Composer itself reads as the path to composer.json)
COMPOSER_BIN ?= $(if $(wildcard composer.phar),php composer.phar,composer)

all : lint tests style phpstan psalm composer-unused composer-require-checker

tests :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml

lint :
	vendor/bin/parallel-lint src tests

style :
	vendor/bin/phpcs --standard=tools/coding_standard.xml -s src tests

phpstan :
	vendor/bin/phpstan analyze -c tools/phpstan.neon --memory-limit=512M

psalm :
	vendor/bin/psalm -c tools/psalm.xml

phpmd :
	vendor/bin/phpmd src/ ansi tools/phpmd.xml

composer-unused :
	vendor/bin/composer-unused

composer-require-checker :
	vendor/bin/composer-require-checker check composer.json

composer-audit :
	$(COMPOSER_BIN) audit

coverage :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml --coverage-text=php://stdout
