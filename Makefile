.PHONY : tests lint static report

all : tests lint static report

tests :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml

lint :
	vendor/bin/phpcs --standard=coding_standard.xml --ignore=vendor .

static :
	vendor/bin/phpstan analyze --level max src/
	vendor/bin/phpmd src/ text cleancode,codesize,design,unusedcode,naming

report :
	vendor/bin/phploc src/
