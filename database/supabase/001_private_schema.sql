-- Run once in the SQL Editor of the DEDICATED MarketHub Supabase project.
-- Laravel uses direct PostgreSQL connections, NOT Supabase's Data API.
-- Do NOT add this schema to the Supabase exposed schemas list.
CREATE SCHEMA IF NOT EXISTS markethub AUTHORIZATION postgres;
REVOKE ALL ON SCHEMA markethub FROM PUBLIC, anon, authenticated, service_role;

-- Protect current and future tables, functions, and sequences from Data API roles.
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA markethub REVOKE ALL ON TABLES FROM anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA markethub REVOKE ALL ON SEQUENCES FROM anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA markethub REVOKE ALL ON FUNCTIONS FROM anon, authenticated, service_role;

-- Laravel migrations will create the tables automatically in this schema
-- when DB_CONNECTION=pgsql and DB_SEARCH_PATH=markethub.
