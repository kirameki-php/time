.PHONY: build
build:
	docker compose pull
	docker compose build --pull app

.PHONY: up
up:
	docker compose up -d

.PHONY: down
down:
	docker compose down --remove-orphans

.PHONY: ps
ps:
	docker compose ps -a

.PHONY: logs
logs:
	docker compose logs

.PHONY: test
test:
	docker compose run --rm app phpunit

.PHONY: analyse
analyze:
	docker compose run --rm app composer analyze

.PHONY: bash
bash:
	docker compose run --rm app bash --login

.PHONY: update
update:
	docker compose run --rm app composer update
