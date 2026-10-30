.PHONY : lint tests style phpstan phpmd psalm composer-unused composer-require-checker composer-audit coverage

# Avoid worker sockets and user-cache writes; callers may override these flags.
PHPSTAN_FLAGS ?= --debug --no-progress
PSALM_FLAGS ?= --threads=1 --no-cache --no-progress

all : lint tests style phpstan psalm composer-unused composer-require-checker

tests :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml

lint :
	vendor/bin/parallel-lint src tests

style :
	vendor/bin/phpcs --standard=tools/coding_standard.xml -s src tests

phpstan :
	vendor/bin/phpstan analyze -c tools/phpstan.neon $(PHPSTAN_FLAGS)

psalm :
	vendor/bin/psalm -c tools/psalm.xml $(PSALM_FLAGS)

phpmd :
	vendor/bin/phpmd src/ ansi tools/phpmd.xml

composer-unused :
	vendor/bin/composer-unused

composer-require-checker :
	vendor/bin/composer-require-checker check composer.json

composer-audit :
	./composer.phar audit

coverage :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml --coverage-text=php://stdout
