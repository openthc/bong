--
-- Name: b2b_incoming; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2b_incoming (
    id character varying(64) NOT NULL,
    source_license_id character varying(64) NOT NULL,
    target_license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb,
    name text
);


ALTER TABLE public.b2b_incoming OWNER TO openthc_bong;

--
-- Name: b2b_incoming_item; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2b_incoming_item (
    id character varying(64) NOT NULL,
    b2b_incoming_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb,
    name text
);


ALTER TABLE public.b2b_incoming_item OWNER TO openthc_bong;

--
-- Name: b2b_outgoing; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2b_outgoing (
    id character varying(64) NOT NULL,
    source_license_id character varying(64) NOT NULL,
    target_license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb,
    name text
);


ALTER TABLE public.b2b_outgoing OWNER TO openthc_bong;

--
-- Name: b2b_outgoing_file; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2b_outgoing_file (
    id character varying(64) NOT NULL,
    name text,
    body bytea
);


ALTER TABLE public.b2b_outgoing_file OWNER TO openthc_bong;

--
-- Name: b2b_outgoing_item; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2b_outgoing_item (
    id character varying(64) NOT NULL,
    b2b_outgoing_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb,
    name text
);


ALTER TABLE public.b2b_outgoing_item OWNER TO openthc_bong;

--
-- Name: b2c_sale; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2c_sale (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb,
    name text
);


ALTER TABLE public.b2c_sale OWNER TO openthc_bong;

--
-- Name: b2c_sale_item; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.b2c_sale_item (
    id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    b2c_sale_id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb,
    name text
);


ALTER TABLE public.b2c_sale_item OWNER TO openthc_bong;

--
-- Name: base_option; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.base_option (
    id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    key character varying(256),
    val jsonb
);


ALTER TABLE public.base_option OWNER TO openthc_bong;

--
-- Name: company; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.company (
    id character varying(26) NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone
    hash character varying(64),
    name text
);


ALTER TABLE public.company OWNER TO openthc_bong;

--
-- Name: contact; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.contact (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.contact OWNER TO openthc_bong;

--
-- Name: crop; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.crop (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.crop OWNER TO openthc_bong;

--
-- Name: disposal; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.disposal (
    id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb
);


ALTER TABLE public.disposal OWNER TO openthc_bong;

--
-- Name: inventory; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.inventory (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.inventory OWNER TO openthc_bong;

--
-- Name: inventory_adjust; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.inventory_adjust (
    id character varying(64) NOT NULL,
    inventory_id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.inventory_adjust OWNER TO openthc_bong;

--
-- Name: lab_result; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.lab_result (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer,
    stat integer,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb
);


ALTER TABLE public.lab_result OWNER TO openthc_bong;

--
-- Name: lab_result_metric; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.lab_result_metric (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    lab_result_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text NOT NULL,
    data jsonb
);


ALTER TABLE public.lab_result_metric OWNER TO openthc_bong;

--
-- Name: license; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.license (
    id text NOT NULL,
    company_id character varying(26) NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    code text NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text NOT NULL,
    data jsonb
);


ALTER TABLE public.license OWNER TO openthc_bong;

--
-- Name: log_audit; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.log_audit (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    company_id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    name text,
    req jsonb,
    res jsonb,
    req_info jsonb,
    res_info jsonb
);


ALTER TABLE public.log_audit OWNER TO openthc_bong;

--
-- Name: log_delta; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.log_delta (
    id character varying(64) DEFAULT public.ulid_create() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    command character varying(8) NOT NULL,
    subject character varying(64) NOT NULL,
    subject_id character varying(64) NOT NULL,
    v0 jsonb,
    v1 jsonb
);


ALTER TABLE public.log_delta OWNER TO postgres;

--
-- Name: log_upload; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.log_upload (
    id character varying(64) NOT NULL,
    license_id character varying(26) NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    name text,
    source_data jsonb,
    result_data jsonb,
    req_info jsonb,
    res_info jsonb
);


ALTER TABLE public.log_upload OWNER TO openthc_bong;

--
-- Name: product; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.product (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.product OWNER TO openthc_bong;

--
-- Name: section; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.section (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.section OWNER TO openthc_bong;

--
-- Name: uom; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.uom (
    id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    data jsonb
);


ALTER TABLE public.uom OWNER TO postgres;

--
-- Name: upload_object_action; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.upload_object_action (
    id character varying(26) NOT NULL,
    upload_id character varying(26),
    object_id character varying(26),
    object_type character varying(64) NOT NULL,
    action character varying(8) NOT NULL
);


ALTER TABLE public.upload_object_action OWNER TO openthc_bong;

--
-- Name: variety; Type: TABLE; Schema: public; Owner: openthc_bong
--

CREATE TABLE public.variety (
    id character varying(100) NOT NULL,
    license_id character varying(64) NOT NULL,
    flag integer DEFAULT 0 NOT NULL,
    stat integer DEFAULT 100 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.variety OWNER TO openthc_bong;

--
-- Name: vehicle; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.vehicle (
    id character varying(64) NOT NULL,
    license_id character varying(64) NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone,
    hash character varying(64),
    name text,
    data jsonb
);


ALTER TABLE public.vehicle OWNER TO postgres;
