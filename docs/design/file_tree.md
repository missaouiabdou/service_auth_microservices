# Authentication Microservice - File Structure

```
auth-service/
├── docker/
│   ├── php/
│   │   ├── Dockerfile                 # Multi-stage PHP 8.4 Dockerfile
│   │   └── php.ini                    # PHP configuration
│   ├── nginx/
│   │   ├── Dockerfile                 # Nginx Dockerfile
│   │   ├── nginx.conf                 # Main Nginx configuration
│   │   └── default.conf               # Virtual host configuration
│   └── postgres/
│       └── init.sql                   # Database initialization script
│
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml              # Doctrine ORM configuration
│   │   ├── lexik_jwt_authentication.yaml  # JWT configuration
│   │   ├── messenger.yaml             # Symfony Messenger configuration
│   │   ├── monolog.yaml               # Logging configuration
│   │   ├── security.yaml              # Security and firewall configuration
│   │   ├── validator.yaml             # Validation configuration
│   │   └── cache.yaml                 # Cache configuration
│   ├── routes/
│   │   └── api.yaml                   # API routes definition
│   ├── services.yaml                  # Service container configuration
│   └── bundles.php                    # Bundle registration
│
├── src/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── User.php               # User entity
│   │   │   └── OutboxEvent.php        # Outbox event entity
│   │   ├── ValueObject/
│   │   │   ├── Email.php              # Email value object
│   │   │   ├── Password.php           # Password value object
│   │   │   └── UserId.php             # User ID value object
│   │   ├── Event/
│   │   │   ├── UserCreatedEvent.php   # User created domain event
│   │   │   └── UserAuthenticatedEvent.php  # User authenticated event
│   │   ├── Repository/
│   │   │   ├── IUserRepository.php    # User repository interface
│   │   │   └── IOutboxRepository.php  # Outbox repository interface
│   │   └── Enum/
│   │       ├── OutboxStatus.php       # Outbox status enum
│   │       └── CircuitState.php       # Circuit breaker state enum
│   │
│   ├── Application/
│   │   ├── DTO/
│   │   │   ├── RegisterUserDTO.php    # Registration DTO
│   │   │   ├── AuthenticationDTO.php  # Authentication DTO
│   │   │   ├── TokenDTO.php           # Token DTO
│   │   │   └── ChangePasswordDTO.php  # Change password DTO
│   │   ├── Service/
│   │   │   ├── RegistrationService.php      # User registration service
│   │   │   ├── AuthenticationService.php    # Authentication service
│   │   │   ├── TokenService.php             # Token management service
│   │   │   ├── OutboxProcessor.php          # Outbox event processor
│   │   │   ├── CircuitBreaker.php           # Circuit breaker implementation
│   │   │   └── RateLimiter.php              # Rate limiter implementation
│   │   └── Command/
│   │       ├── ProcessOutboxEventsCommand.php  # CLI command for outbox processing
│   │       └── GenerateJwtKeysCommand.php      # CLI command for JWT key generation
│   │
│   ├── Infrastructure/
│   │   ├── Persistence/
│   │   │   ├── Repository/
│   │   │   │   ├── UserRepository.php       # User repository implementation
│   │   │   │   └── OutboxRepository.php     # Outbox repository implementation
│   │   │   └── Doctrine/
│   │   │       ├── Type/
│   │   │       │   ├── EmailType.php        # Doctrine type for Email VO
│   │   │       │   ├── PasswordType.php     # Doctrine type for Password VO
│   │   │       │   └── UserIdType.php       # Doctrine type for UserId VO
│   │   │       └── Migration/
│   │   │           ├── Version20240105000001.php  # Initial schema
│   │   │           └── Version20240105000002.php  # Add audit tables
│   │   ├── Messaging/
│   │   │   ├── MessagePublisher.php         # Message publisher implementation
│   │   │   └── Handler/
│   │   │       └── UserCreatedEventHandler.php  # Event handler
│   │   └── Security/
│   │       ├── JwtTokenAuthenticator.php    # JWT authenticator
│   │       └── UserProvider.php             # User provider for security
│   │
│   ├── Presentation/
│   │   ├── Controller/
│   │   │   ├── AuthController.php           # Authentication endpoints
│   │   │   └── HealthController.php         # Health check endpoints
│   │   ├── Middleware/
│   │   │   ├── RateLimitMiddleware.php      # Rate limiting middleware
│   │   │   └── CorrelationIdMiddleware.php  # Request correlation middleware
│   │   └── Exception/
│   │       ├── Handler/
│   │       │   └── GlobalExceptionHandler.php  # Global exception handler
│   │       └── Custom/
│   │           ├── AuthenticationException.php
│   │           ├── ValidationException.php
│   │           └── RateLimitException.php
│   │
│   └── Kernel.php                       # Symfony kernel
│
├── migrations/                          # Database migrations directory
│
├── var/
│   ├── cache/                           # Application cache
│   ├── log/                             # Application logs
│   └── jwt/                             # JWT keys storage
│       ├── private.pem                  # RSA private key (gitignored)
│       └── public.pem                   # RSA public key
│
├── tests/
│   ├── Unit/
│   │   ├── Domain/
│   │   │   ├── Entity/
│   │   │   │   ├── UserTest.php
│   │   │   │   └── OutboxEventTest.php
│   │   │   └── ValueObject/
│   │   │       ├── EmailTest.php
│   │   │       ├── PasswordTest.php
│   │   │       └── UserIdTest.php
│   │   ├── Application/
│   │   │   └── Service/
│   │   │       ├── RegistrationServiceTest.php
│   │   │       ├── AuthenticationServiceTest.php
│   │   │       ├── TokenServiceTest.php
│   │   │       ├── CircuitBreakerTest.php
│   │   │       └── RateLimiterTest.php
│   │   └── Infrastructure/
│   │       └── Persistence/
│   │           └── Repository/
│   │               ├── UserRepositoryTest.php
│   │               └── OutboxRepositoryTest.php
│   ├── Integration/
│   │   ├── Controller/
│   │   │   └── AuthControllerTest.php
│   │   └── Messaging/
│   │       └── OutboxProcessorTest.php
│   └── Functional/
│       ├── RegistrationFlowTest.php
│       ├── AuthenticationFlowTest.php
│       └── TokenRefreshFlowTest.php
│
├── public/
│   └── index.php                        # Application entry point
│
├── bin/
│   └── console                          # Symfony console
│
├── .env                                 # Environment variables (template)
├── .env.local                           # Local environment overrides (gitignored)
├── .env.test                            # Test environment variables
├── .gitignore                           # Git ignore rules
├── composer.json                        # PHP dependencies
├── composer.lock                        # Locked PHP dependencies
├── docker-compose.yml                   # Docker Compose configuration
├── Makefile                             # Common commands shortcuts
├── phpunit.xml.dist                     # PHPUnit configuration
├── symfony.lock                         # Symfony Flex lock file
└── README.md                            # Project documentation
```

## Key Directories Explanation

### `/docker`
Contains all Docker-related configuration files for containerization:
- **php/**: PHP-FPM container configuration with multi-stage build
- **nginx/**: Nginx web server configuration for API gateway
- **postgres/**: PostgreSQL database initialization scripts

### `/config`
Symfony configuration files organized by bundle and environment:
- **packages/**: Bundle-specific configurations (Doctrine, JWT, Messenger, etc.)
- **routes/**: API route definitions
- **services.yaml**: Dependency injection container configuration

### `/src/Domain`
Core business logic following Domain-Driven Design principles:
- **Entity/**: Domain entities (User, OutboxEvent)
- **ValueObject/**: Immutable value objects (Email, Password, UserId)
- **Event/**: Domain events for event-driven architecture
- **Repository/**: Repository interfaces (implementation in Infrastructure layer)
- **Enum/**: Enumerations for type safety

### `/src/Application`
Application services and use cases:
- **DTO/**: Data Transfer Objects for request/response handling
- **Service/**: Application services implementing business use cases
- **Command/**: CLI commands for background tasks

### `/src/Infrastructure`
Technical implementations and external integrations:
- **Persistence/**: Database access layer with Doctrine ORM
- **Messaging/**: Message broker integration with RabbitMQ
- **Security/**: JWT authentication and authorization

### `/src/Presentation`
HTTP layer handling requests and responses:
- **Controller/**: REST API endpoints
- **Middleware/**: Request/response interceptors
- **Exception/**: Exception handling and error responses

### `/tests`
Comprehensive test suite:
- **Unit/**: Isolated unit tests for individual components
- **Integration/**: Tests for component interactions
- **Functional/**: End-to-end tests for complete user flows

### `/var`
Runtime files and generated content:
- **cache/**: Application cache (gitignored)
- **log/**: Application logs (gitignored)
- **jwt/**: RSA key pair for JWT signing (private key gitignored)

## File Naming Conventions

1. **PHP Classes**: PascalCase (e.g., `UserRepository.php`)
2. **Configuration**: snake_case (e.g., `security.yaml`)
3. **Tests**: Same as class name + `Test` suffix (e.g., `UserTest.php`)
4. **Interfaces**: Prefix with `I` (e.g., `IUserRepository.php`)
5. **Value Objects**: Descriptive nouns (e.g., `Email.php`, `Password.php`)
6. **DTOs**: Suffix with `DTO` (e.g., `RegisterUserDTO.php`)
7. **Events**: Suffix with `Event` (e.g., `UserCreatedEvent.php`)
8. **Commands**: Suffix with `Command` (e.g., `ProcessOutboxEventsCommand.php`)

## Technology Stack

- **PHP**: 8.4 with strict types
- **Framework**: Symfony 7
- **ORM**: Doctrine ORM
- **Database**: PostgreSQL 16
- **Message Broker**: RabbitMQ
- **Authentication**: LexikJWTAuthenticationBundle (RS256)
- **Testing**: PHPUnit
- **Code Quality**: PHP-CS-Fixer, PHPStan
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx