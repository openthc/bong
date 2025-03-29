--
-- PostgreSQL database dump
--

\c openthc_bong

SET check_function_bodies = false;
SET client_encoding = 'UTF8';
SET client_min_messages = warning;
SET default_table_access_method = heap;
SET default_tablespace = '';
SET default_with_oids = false;
SET idle_in_transaction_session_timeout = 0;
SET lock_timeout = 0;
SET row_security = off;
SET search_path TO public;
SET standard_conforming_strings = on;
SET statement_timeout = 0;
SET xmloption = content;

--
-- Name: pg_ulid; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_ulid WITH SCHEMA public;


--
-- Name: EXTENSION pg_ulid; Type: COMMENT; Schema: -; Owner:
--

COMMENT ON EXTENSION pg_ulid IS 'ULID datatype and functions';




ALTER FUNCTION public.log_delta_trigger() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;
