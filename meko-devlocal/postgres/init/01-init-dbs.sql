-- Initialize isolated roles and databases for Liferay 7.4 and Moodle 5.3
-- inside a single shared PostgreSQL 17 container.

DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'liferay') THEN
        CREATE ROLE liferay WITH LOGIN PASSWORD 'LiferayPass2026!@#';
    END IF;
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'lms_user') THEN
        CREATE ROLE lms_user WITH LOGIN PASSWORD 'LmsPassword2026!@#';
    END IF;
END
$$;

SELECT 'CREATE DATABASE lportal OWNER liferay ENCODING ''UTF8'''
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'lportal')\gexec

SELECT 'CREATE DATABASE accees_lms OWNER lms_user ENCODING ''UTF8'''
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'accees_lms')\gexec

GRANT ALL PRIVILEGES ON DATABASE lportal TO liferay;
GRANT ALL PRIVILEGES ON DATABASE accees_lms TO lms_user;
