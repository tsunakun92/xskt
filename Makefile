start:
	docker-compose -f laradock/docker-compose.yml --env-file laradock/.env up -d nginx mysql phpmyadmin workspace redis
stop:
	docker-compose -f laradock/docker-compose.yml --env-file laradock/.env stop
remove:
	docker-compose -f laradock/docker-compose.yml --env-file laradock/.env down
goweb:
	docker exec -it base_web bash
godb:
	docker exec -it base_mysql bash
migrate:
	docker exec -it base_web php artisan migrate --seed
seed:
	docker exec -it base_web php artisan db:seed
admin-seed:
	docker exec -it base_web php artisan module:seed Admin
cps:
	docker exec -it base_web composer install
cps-update:
	docker exec -it base_web composer update
unit-test:
	docker exec -it base_web php artisan test --testsuite=Unit
feature-test:
	docker exec -it base_web php artisan test --testsuite=Feature
yarn:
	docker exec -it base_web yarn
y-dev:
	docker exec -it base_web yarn dev
y-build:
	docker exec -it base_web yarn build