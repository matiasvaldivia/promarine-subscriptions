.PHONY: up down build install migrate seed fresh test logs shell import-promarine
up:
	docker compose up -d
down:
	docker compose down
build:
	docker compose build
install:
	docker compose up -d --build
	docker compose exec app composer install --no-interaction
	docker compose exec app php artisan key:generate
	docker compose exec app npm install
	docker compose exec app npm run build
	docker compose exec app php artisan migrate --seed
migrate:
	docker compose exec app php artisan migrate
seed:
	docker compose exec app php artisan db:seed
fresh:
	docker compose exec app php artisan migrate:fresh --seed
test:
	docker compose exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= app php artisan test
logs:
	docker compose logs -f --tail=150
shell:
	docker compose exec app sh
import-promarine:
	docker compose exec app php artisan promarine:import
