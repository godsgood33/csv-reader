#!/bin/sh

docker run --rm -v $(pwd):/app -w /app php:7.4 vendor/bin/phpunit > php7.4-test.txt && vendor/bin/phpstan analyse src --level=9 >> php7.4-test.txt
docker run --rm -v $(pwd):/app -w /app php:8.0 vendor/bin/phpunit > php8.0-test.txt && vendor/bin/phpstan analyse src --level=9 >> php8.0-test.txt
docker run --rm -v $(pwd):/app -w /app php:8.1 vendor/bin/phpunit > php8.1-test.txt && vendor/bin/phpstan analyse src --level=9 >> php8.1-test.txt
docker run --rm -v $(pwd):/app -w /app php:8.2 vendor/bin/phpunit > php8.2-test.txt && vendor/bin/phpstan analyse src --level=9 >> php8.2-test.txt
