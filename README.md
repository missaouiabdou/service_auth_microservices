# Authentication Microservice

A production-ready Authentication Microservice built with Symfony 7 and PHP 8.4, following Clean Architecture and SOLID principles. This service provides stateless JWT authentication for ERP+CRM microservices architecture.

## 🎯 Features

- **Stateless JWT Authentication** with RS256 algorithm
- **Outbox Pattern** for reliable event publishing
- **Circuit Breaker** for resilience against cascading failures
- **Rate Limiting** to prevent brute force attacks
- **Event-Driven Architecture** with RabbitMQ
- **Clean Architecture** with clear separation of concerns
- **SOLID Principles** throughout the codebase
- **Comprehensive Testing** (Unit, Integration, Functional)
- **Docker-based** deployment with multi-stage builds
- **Health Checks** for orchestration and monitoring

## 📋 Prerequisites

- Docker 24.0+
- Docker Compose 2.20+
- Make (optional, for convenience commands)

## 🚀 Quick Start

### 1. Clone and Setup

```bash
# Clone the repository
git clone <repository-url>
cd auth-service

# Copy environment file
cp .env .env.local

# Generate JWT keys
make generate-keys

# Start services
make up
```

### 2. Initialize Database

```bash
# Run migrations
make migrate

# (Optional) Load fixtures for development
make fixtures
```

### 3. Verify Installation

```bash
# Check health
curl http://localhost:8080/api/health

# Expected response:
# {"status":"healthy","timestamp":"2024-01-05T10:30:00+00:00"}
```

## 🏗️ Architecture

The service follows Clean Architecture with four main layers:

### Domain Layer
- **Entities**: User, OutboxEvent
- **Value Objects**: Email, Password, UserId
- **Events**: UserCreatedEvent, UserAuthenticatedEvent
- **Repository Interfaces**: IUserRepository, IOutboxRepository

### Application Layer
- **Services**: RegistrationService, AuthenticationService, TokenService
- **DTOs**: RegisterUserDTO, AuthenticationDTO, TokenDTO
- **Commands**: ProcessOutboxEventsCommand, GenerateJwtKeysCommand

### Infrastructure Layer
- **Persistence**: Doctrine ORM repositories
- **Messaging**: RabbitMQ integration with Symfony Messenger
- **Security**: JWT authentication with LexikJWTAuthenticationBundle

### Presentation Layer
- **Controllers**: AuthController, HealthController
- **Middleware**: RateLimitMiddleware, CorrelationIdMiddleware
- **Exception Handlers**: GlobalExceptionHandler

For detailed architecture diagrams, see [System Design Document](docs/design/system_design.md).

## 📡 API Endpoints

### Authentication

#### Register User
```bash
POST /api/register
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePass123!",
    "name": "John Doe"
}

Response: 201 Created
{
    "user": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "email": "user@example.com",
        "name": "John Doe",
        "roles": ["ROLE_USER"]
    },
    "token": {
        "accessToken": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
        "refreshToken": "def50200a1b2c3d4e5f6...",
        "expiresIn": 900,
        "tokenType": "Bearer"
    }
}
```

#### Login
```bash
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "SecurePass123!"
}

Response: 200 OK
{
    "accessToken": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "def50200a1b2c3d4e5f6...",
    "expiresIn": 900,
    "tokenType": "Bearer"
}
```

#### Refresh Token
```bash
POST /api/token/refresh
Content-Type: application/json

{
    "refreshToken": "def50200a1b2c3d4e5f6..."
}

Response: 200 OK
{
    "accessToken": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "ghi78900xyz...",
    "expiresIn": 900,
    "tokenType": "Bearer"
}
```

#### Change Password
```bash
POST /api/password/change
Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json

{
    "currentPassword": "SecurePass123!",
    "newPassword": "NewSecurePass456!"
}

Response: 200 OK
{
    "message": "Password changed successfully",
    "requiresReauthentication": true
}
```

### Health Checks

#### Health Check
```bash
GET /api/health

Response: 200 OK
{
    "status": "healthy",
    "timestamp": "2024-01-05T10:30:00+00:00"
}
```

#### Readiness Check
```bash
GET /api/health/ready

Response: 200 OK
{
    "status": "ready",
    "checks": {
        "database": "ok",
        "cache": "ok",
        "messaging": "ok"
    }
}
```

## 🔐 Security

### JWT Token Structure

**Access Token** (15 minutes TTL):
```json
{
    "sub": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "roles": ["ROLE_USER"],
    "iat": 1704451200,
    "exp": 1704452100,
    "iss": "auth-service"
}
```

**Refresh Token** (7 days TTL):
- Random 64-byte string
- Stored in database
- One-time use (rotated on refresh)
- Revoked on password change

### Password Requirements

- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character

### Rate Limiting

- **Registration**: 5 attempts per 15 minutes per IP
- **Login**: 5 attempts per 15 minutes per email
- **Token Refresh**: 10 attempts per 15 minutes per IP
- **Password Change**: 3 attempts per 15 minutes per user

## 🔄 Event-Driven Architecture

### Outbox Pattern

The service implements the Outbox Pattern to ensure reliable event publishing:

1. User registration saves both User and OutboxEvent in a single transaction
2. Background processor reads pending outbox events
3. Events are published to RabbitMQ with Circuit Breaker protection
4. Successfully published events are marked as processed
5. Failed events are retried with exponential backoff

### Published Events

#### UserCreatedEvent
```json
{
    "eventType": "UserCreated",
    "userId": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "name": "John Doe",
    "roles": ["ROLE_USER"],
    "occurredAt": "2024-01-05T10:30:00+00:00"
}
```

Routing Key: `user.created`

Consumers: CRM Service, ERP Service

## 🛡️ Reliability Patterns

### Circuit Breaker

Protects against cascading failures when publishing events to RabbitMQ:

- **Failure Threshold**: 5 consecutive failures
- **Timeout**: 60 seconds
- **States**: CLOSED → OPEN → HALF_OPEN → CLOSED

### Rate Limiter

Prevents brute force attacks and API abuse:

- **Storage**: Redis cache
- **Algorithm**: Token bucket
- **Response**: 429 Too Many Requests with Retry-After header

## 🧪 Testing

### Run All Tests
```bash
make test
```

### Run Specific Test Suites
```bash
# Unit tests only
make test-unit

# Integration tests only
make test-integration

# Functional tests only
make test-functional
```

### Code Coverage
```bash
make coverage
```

### Code Quality
```bash
# PHP CS Fixer
make cs-fix

# PHPStan
make phpstan
```

## 📊 Monitoring

### Metrics Endpoints

- `/api/health` - Basic health check
- `/api/health/ready` - Readiness probe
- `/api/health/live` - Liveness probe

### Logging

Logs are written to `var/log/` directory:

- `dev.log` - Development logs
- `prod.log` - Production logs
- `error.log` - Error logs only

Log format: JSON structured logging with correlation IDs

### Observability

Recommended monitoring stack:

- **Metrics**: Prometheus + Grafana
- **Logging**: ELK Stack (Elasticsearch, Logstash, Kibana)
- **Tracing**: Jaeger or Zipkin
- **Alerting**: Alertmanager

## 🐳 Docker Configuration

### Services

- **php-fpm**: PHP 8.4 with Symfony application
- **nginx**: Web server and API gateway
- **postgres**: PostgreSQL 16 database
- **rabbitmq**: Message broker with management UI

### Ports

- `8080` - Nginx (API Gateway)
- `5432` - PostgreSQL
- `5672` - RabbitMQ AMQP
- `15672` - RabbitMQ Management UI

### Volumes

- `postgres_data` - Database persistence
- `rabbitmq_data` - Message broker persistence

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env.local`:

```bash
# Database
DATABASE_URL=postgresql://user:password@postgres:5432/auth_db

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/var/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/var/jwt/public.pem
JWT_PASSPHRASE=your-secret-passphrase
JWT_TTL=900

# RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages

# Redis
REDIS_URL=redis://redis:6379

# Application
APP_ENV=prod
APP_SECRET=your-app-secret
```

## 📚 Documentation

- [System Design](docs/design/system_design.md) - Comprehensive system design document
- [Architecture Diagram](docs/design/architect.plantuml) - PlantUML architecture diagram
- [Class Diagram](docs/design/class_diagram.plantuml) - Detailed class relationships
- [Sequence Diagram](docs/design/sequence_diagram.plantuml) - Request/response flows
- [ER Diagram](docs/design/er_diagram.plantuml) - Database schema
- [File Structure](docs/design/file_tree.md) - Project organization

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Write comprehensive tests (minimum 80% coverage)
- Document all public APIs
- Use strict types in all PHP files
- Follow SOLID principles

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👥 Authors

- **Development Team** - Initial work

## 🙏 Acknowledgments

- Symfony Framework
- LexikJWTAuthenticationBundle
- Doctrine ORM
- RabbitMQ
- PostgreSQL

## 📞 Support

For support, email support@example.com or join our Slack channel.

## 🗺️ Roadmap

- [ ] Add OAuth2 support (Google, GitHub, etc.)
- [ ] Implement 2FA (TOTP)
- [ ] Add password reset via email
- [ ] Implement account lockout after failed attempts
- [ ] Add API versioning
- [ ] Implement GraphQL endpoint
- [ ] Add WebSocket support for real-time notifications
- [ ] Implement RBAC (Role-Based Access Control)
- [ ] Add multi-tenancy support
- [ ] Implement audit logging dashboard