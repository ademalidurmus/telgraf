all: help

env:
	@cp ./.env.example .env
	@echo ".env file has been created with default environments."

clean:
	@echo "Removing environments..."
	@rm -rf .env

	@echo "Removing log files..."
	@rm -rf ./storage/logs/telgraf.log

	@docker-compose stop
	@docker-compose rm --force

build:
	@docker-compose up -d --build

up:
	@docker-compose up -d

restart:
	@docker-compose restart

status:
	@docker-compose ps

down:
	@docker-compose down

cli:
	@docker exec -it telgraf /bin/bash

logs:
	@tail -f ./storage/logs/*

set_webhook:
	@docker exec -it telgraf /bin/bash -c "php ./app/console.php set_webhook"
	@echo ""

delete_webhook:
	@docker exec -it telgraf /bin/bash -c "php ./app/console.php delete_webhook"
	@echo ""

help:
	@echo ""
	@echo "Usage:             make COMMAND"
	@echo ""
	@echo "Commands:"
	@echo "    env            	Create a new .env file from .env.example"
	@echo "    build          	Build telgraf"
	@echo "    up             	Serve telgraf"
	@echo "    status         	Show service status"
	@echo "    set_webhook    	Set telegram webhook"
	@echo "    delete_webhook   Delete telegram webhook"
	@echo "    restart        	Restart telgraf"
	@echo "    cli            	Enter telgraf bash"
	@echo "    down           	Stop telgraf"
	@echo "    clean          	Clean telgraf"
