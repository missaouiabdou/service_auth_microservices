-- Authentication Service Database Initialization
-- PostgreSQL 16

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Set timezone
SET timezone = 'UTC';

-- Create custom types
DO $$ BEGIN
    CREATE TYPE outbox_status AS ENUM ('PENDING', 'PROCESSED', 'FAILED');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- Performance tuning for authentication workload
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET maintenance_work_mem = '64MB';
ALTER SYSTEM SET checkpoint_completion_target = 0.9;
ALTER SYSTEM SET wal_buffers = '16MB';
ALTER SYSTEM SET default_statistics_target = 100;
ALTER SYSTEM SET random_page_cost = 1.1;
ALTER SYSTEM SET effective_io_concurrency = 200;
ALTER SYSTEM SET work_mem = '4MB';
ALTER SYSTEM SET min_wal_size = '1GB';
ALTER SYSTEM SET max_wal_size = '4GB';

-- Reload configuration
SELECT pg_reload_conf();

-- Grant permissions
GRANT ALL PRIVILEGES ON DATABASE auth_db TO auth_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO auth_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO auth_user;

-- Log initialization
DO $$
BEGIN
    RAISE NOTICE 'Authentication Service database initialized successfully';
END $$;