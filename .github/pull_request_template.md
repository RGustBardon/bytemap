Before submitting a pull request, please make sure the following is done:
1. `php-cs-fixer fix` has been run for every file changed.
2. `phpstan analyse --level 7 .php_cs.dist benchmark.php src tests` reports no problems.
3. `vendor/bin/phpunit` reports no errors.
4. Code coverage (`phpdbg -qrr vendor/bin/phpunit --coverage-text --coverage-html=build/logs/clover`) has not decreased.
5. In case of optimization, a relevant benchmark exists.
6. In case of new features or bugfixes, relevant tests exist.
