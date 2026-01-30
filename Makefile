.PHONY: help dev stop restart clean logs cache-clear db-shell db-reset build install test

# Colors for output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
RED    := \033[0;31m
NC     := \033[0m # No Color

help: ## Show this help message
	@echo "$(GREEN)Authentication Microservice - Makefile Commands$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'

dev: ## 🚀 Start development environment (complete setup)
	@echo "$(GREEN)========================================$(NC)"
	@echo "$(GREEN) Starting Development Environment$(NC)"
	@echo "$(GREEN)========================================$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 1/9:$(NC) Checking environment file..."
	@if [ ! -f .env.local ]; then \
		echo "$(YELLOW)Creating .env.local from .env...$(NC)"; \
		cp .env .env.local; \
	else \
		echo "$(GREEN)✓ .env.local already exists$(NC)"; \
	fi
	@echo ""
	@echo "$(YELLOW)Step 2/9:$(NC) Generating JWT keys..."
	@if [ ! -f config/jwt/private.pem ]; then \
		chmod +x bin/generate-jwt-keys.sh; \
		./bin/generate-jwt-keys.sh; \
		echo "$(GREEN)✓ JWT keys generated$(NC)"; \
	else \
		echo "$(GREEN)✓ JWT keys already exist$(NC)"; \
	fi
	@echo ""
	@echo "$(YELLOW)Step 3/9:$(NC) Building Docker images..."
	docker-compose build
	@echo "$(GREEN)✓ Docker images built$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 4/9:$(NC) Starting Docker containers..."
	docker-compose up -d
	@echo "$(GREEN)✓ Containers started$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 5/9:$(NC) Waiting for services to be ready..."
	@sleep 10
	@echo "$(GREEN)✓ Services ready$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 6/9:$(NC) Installing Composer dependencies..."
	docker-compose exec -T -e XDEBUG_MODE=off php composer install --no-scripts
	@echo "$(GREEN)✓ Dependencies installed$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 7/9:$(NC) Creating database..."
	@docker-compose exec -T php php bin/console doctrine:database:create --if-not-exists || true
	@echo "$(GREEN)✓ Database created$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 8/9:$(NC) Running migrations..."
	docker-compose exec -T php bin/console cache:clear
	docker-compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✓ Migrations executed$(NC)"
	@echo ""
	@echo "$(YELLOW)Step 9/9:$(NC) Testing API health..."
	@sleep 3
	@curl -s http://localhost:8080/api/health || echo "$(RED)API not ready yet, please wait a moment$(NC)"
	@echo ""
	@echo "$(GREEN)========================================$(NC)"
	@echo "$(GREEN)✓ Development environment ready!$(NC)"
	@echo "$(GREEN)========================================$(NC)"
	@echo ""
	@echo "$(YELLOW)Services available:$(NC)"
	@echo "  • API:              http://localhost:8080"
	@echo "  • RabbitMQ UI:      http://localhost:15672 (guest/guest)"
	@echo "  • PostgreSQL:       localhost:5432 (auth_user/auth_pass)"
	@echo ""
	@echo "$(YELLOW)Useful commands:$(NC)"
	@echo "  • make logs         - View logs"
	@echo "  • make stop         - Stop services"
	@echo "  • make restart      - Restart services"
	@echo "  • make test         - Run tests"
	@echo "  • make help         - Show all commands"
	@echo ""

build: ## Build Docker images
	@echo "$(YELLOW)Building Docker images...$(NC)"
	docker-compose build
	@echo "$(GREEN)✓ Build complete$(NC)"

up: ## Start containers
	@echo "$(YELLOW)Starting containers...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✓ Containers started$(NC)"

stop: ## Stop all containers
	@echo "$(YELLOW)Stopping containers...$(NC)"
	docker-compose stop
	@echo "$(GREEN)✓ Containers stopped$(NC)"

down: ## Stop and remove containers
	@echo "$(YELLOW)Stopping and removing containers...$(NC)"
	docker-compose down
	@echo "$(GREEN)✓ Containers removed$(NC)"

restart: ## Restart all containers
	@echo "$(YELLOW)Restarting containers...$(NC)"
	docker-compose restart
	@echo "$(GREEN)✓ Containers restarted$(NC)"

clean: ## Stop containers and remove volumes (⚠️  deletes database)
	@echo "$(RED)⚠️  This will delete all data including the database!$(NC)"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v; \
		rm -rf var/cache/* var/log/*; \
		echo "$(GREEN)✓ Cleanup complete$(NC)"; \
	else \
		echo "$(YELLOW)Cleanup cancelled$(NC)"; \
	fi

logs: ## Show logs from all containers
	docker-compose logs -f

logs-php: ## Show PHP container logs
	docker-compose logs -f php

logs-nginx: ## Show Nginx container logs
	docker-compose logs -f nginx

logs-db: ## Show PostgreSQL container logs
	docker-compose logs -f postgres

shell: ## Access PHP container shell
	docker-compose exec php bash

db-shell: ## Access PostgreSQL shell
	docker-compose exec postgres psql -U auth_user -d auth_db

cache-clear: ## Clear Symfony cache
	@echo "$(YELLOW)Clearing cache...$(NC)"
	docker-compose exec php php bin/console cache:clear
	@echo "$(GREEN)✓ Cache cleared$(NC)"

db-reset: ## Reset database (drop, create, migrate)
	@echo "$(YELLOW)Resetting database...$(NC)"
	docker-compose exec php php bin/console doctrine:database:drop --force --if-exists
	docker-compose exec php php bin/console doctrine:database:create
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✓ Database reset complete$(NC)"

migration: ## Create a new migration
	docker-compose exec php php bin/console make:migration

migrate: ## Run pending migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

test: ## Run tests
	@echo "$(YELLOW)Running tests...$(NC)"
	docker-compose exec php php bin/phpunit
	@echo "$(GREEN)✓ Tests complete$(NC)"

install: ## Install Composer dependencies
	@echo "$(YELLOW)Installing dependencies...$(NC)"
	docker-compose exec php composer install
	@echo "$(GREEN)✓ Dependencies installed$(NC)"

update: ## Update Composer dependencies
	@echo "$(YELLOW)Updating dependencies...$(NC)"
	docker-compose exec php composer update
	@echo "$(GREEN)✓ Dependencies updated$(NC)"

jwt-keys: ## Generate JWT keys
	@echo "$(YELLOW)Generating JWT keys...$(NC)"
	chmod +x bin/generate-jwt-keys.sh
	./bin/generate-jwt-keys.sh
	@echo "$(GREEN)✓ JWT keys generated$(NC)"

status: ## Show status of all containers
	@echo "$(YELLOW)Container Status:$(NC)"
	@docker-compose ps

health: ## Check API health
	@echo "$(YELLOW)Checking API health...$(NC)"
	@curl -s http://localhost:8080/api/health | python3 -m json.tool || echo "$(RED)API not responding$(NC)"

prod: ## Build and start production environment
	@echo "$(YELLOW)Starting production environment...$(NC)"
	docker-compose -f docker-compose.prod.yml build
	docker-compose -f docker-compose.prod.yml up -d
	@echo "$(GREEN)✓ Production environment started$(NC)"
