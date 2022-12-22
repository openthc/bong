--
-- BONG Database
-- Make one per License
--

create table base_option (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	key varchar(256),
	val jsonb
);

create table company (
	id varchar(64) PRIMARY KEY,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table contact (
	id varchar(64) PRIMARY KEY,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table license (
	id varchar(64) PRIMARY KEY,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	code text,
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table license_meta (
	id varchar(26) primary key,
	license_id varchar(26) not null references license(id),
	key varchar(64),
	val jsonb
);


create table vehicle (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table section (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table product (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table variety (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table crop (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table lot (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table lot_delta (
	id varchar(64) PRIMARY KEY,
	lot_id varchar(64) NOT NULL,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table lab_result (
	id varchar(64) PRIMARY KEY NOT NULL,
	license_id varchar(64),
	flag int NOT NULL DEFAULT 0,
	stat int NOT NULL DEFAULT 100,
	created_at timestamp with time zone not null default now(),
	updated_at timestamp with time zone not null default now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table lab_result_metric (
	id varchar(64) PRIMARY KEY NOT NULL,
	license_id varchar(64),
	lab_result varchar(64),
	flag int NOT NULL DEFAULT 0,
	stat int NOT NULL DEFAULT 100,
	created_at timestamp with time zone not null default now(),
	updated_at timestamp with time zone not null default now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

create table b2b_incoming (
	id varchar(64) PRIMARY KEY,
	source_license_id varchar(64) not null,
	target_license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table b2b_incoming_item (
	id varchar(64) PRIMARY KEY,
	b2b_incoming_id varchar(64) NOT NULL,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table b2b_outgoing (
	id varchar(64) PRIMARY KEY,
	source_license_id varchar(64) not null,
	target_license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table b2b_outgoing_item (
	id varchar(64) PRIMARY KEY,
	b2b_outgoing_id varchar(64) NOT NULL,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table b2c_sale (
	id varchar(64) PRIMARY KEY,
	license_id varchar(64) not null,
	flag int not null default 0,
	stat int not null default 100,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table b2c_sale_item (
	id varchar(64) PRIMARY KEY,
	flag int not null default 0,
	stat int not null default 100,
	b2c_sale_id varchar(64) NOT NULL,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	name text,
	data jsonb
);

create table disposal (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64) NOT NULL DEFAULT '-',
	data jsonb
);

-- create table lot_destroy (
-- 	id varchar(64) PRIMARY KEY,
-- 	flag int not null default 0,
-- 	stat int not null default 100,
-- 	created_at timestamp with time zone NOT NULL DEFAULT now(),
-- 	updated_at timestamp with time zone NOT NULL DEFAULT now(),
-- 	hash varchar(64),
-- 	data jsonb
-- );

create table log_audit (
	id varchar(64) not null primary key,
	company_id varchar(64) not null,
	license_id varchar(64) not null,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	name text,
	req jsonb,
	res jsonb
);

create table log_delta (
	id bigserial primary key,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	command varchar(8) not null,
	subject varchar(64) not null,
	subject_id varchar(64) not null,
	v0 jsonb,
	v1 jsonb
);
