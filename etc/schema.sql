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
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table contact (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table license (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table vehicle (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table section (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table product (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table variety (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table batch (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table crop (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table lot (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table lot_delta (
	id varchar(64) PRIMARY KEY,
	lot_id varchar(64) NOT NULL,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);


create table lab_result (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table b2b_sale (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table b2b_sale_item (
	id varchar(64) PRIMARY KEY,
	b2b_sale_id varchar(64) NOT NULL
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table b2c_sale (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
);

create table disposal (
	id varchar(64) PRIMARY KEY,
	created_at timestamp with time zone NOT NULL DEFAULT now(),
	updated_at timestamp with time zone NOT NULL DEFAULT now(),
	hash varchar(64),
	data jsonb
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
