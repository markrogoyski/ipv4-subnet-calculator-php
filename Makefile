.PHONY : lint tests style phpstan phpmd psalm composer-unused composer-require-checker composer-audit coverage

all : lint tests style phpstan psalm composer-unused composer-require-checker

tests :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml

lint :
	vendor/bin/parallel-lint src tests

style :
	vendor/bin/phpcs --standard=tools/coding_standard.xml --ignore=vendor -s .

phpstan :
	vendor/bin/phpstan analyze -c tools/phpstan.neon

psalm :
	vendor/bin/psalm -c tools/psalm.xml

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
