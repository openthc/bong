--
-- Name: b2b_incoming_item b2b_incoming_item_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2b_incoming_item
    ADD CONSTRAINT b2b_incoming_item_pkey PRIMARY KEY (id);


--
-- Name: b2b_incoming b2b_incoming_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2b_incoming
    ADD CONSTRAINT b2b_incoming_pkey PRIMARY KEY (id, target_license_id);


--
-- Name: b2b_outgoing_file b2b_outgoing_file_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2b_outgoing_file
    ADD CONSTRAINT b2b_outgoing_file_pkey PRIMARY KEY (id);


--
-- Name: b2b_outgoing_item b2b_outgoing_item_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2b_outgoing_item
    ADD CONSTRAINT b2b_outgoing_item_pkey PRIMARY KEY (id);


--
-- Name: b2b_outgoing b2b_outgoing_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2b_outgoing
    ADD CONSTRAINT b2b_outgoing_pkey PRIMARY KEY (id, source_license_id);


--
-- Name: b2c_sale_item b2c_sale_item_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2c_sale_item
    ADD CONSTRAINT b2c_sale_item_pkey PRIMARY KEY (id);


--
-- Name: b2c_sale b2c_sale_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2c_sale
    ADD CONSTRAINT b2c_sale_pkey PRIMARY KEY (id);


--
-- Name: base_option base_option_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.base_option
    ADD CONSTRAINT base_option_pkey PRIMARY KEY (id);


--
-- Name: company company_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.company
    ADD CONSTRAINT company_pkey PRIMARY KEY (id);


--
-- Name: crop crop_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.crop
    ADD CONSTRAINT crop_pkey PRIMARY KEY (id, license_id);


--
-- Name: lab_result_metric lab_result_metric_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.lab_result_metric
    ADD CONSTRAINT lab_result_metric_pkey PRIMARY KEY (id);


--
-- Name: lab_result lab_result_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.lab_result
    ADD CONSTRAINT lab_result_pkey PRIMARY KEY (id);


--
-- Name: license license_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.license
    ADD CONSTRAINT license_pkey PRIMARY KEY (id);


--
-- Name: log_audit log_audit_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.log_audit
    ADD CONSTRAINT log_audit_pkey PRIMARY KEY (id);


--
-- Name: log_upload log_upload_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.log_upload
    ADD CONSTRAINT log_upload_pkey PRIMARY KEY (id);


--
-- Name: inventory_adjust lot_delta_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.inventory_adjust
    ADD CONSTRAINT lot_delta_pkey PRIMARY KEY (id);


--
-- Name: inventory lot_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT lot_pkey PRIMARY KEY (id, license_id);


--
-- Name: product product_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.product
    ADD CONSTRAINT product_pkey PRIMARY KEY (id, license_id);


--
-- Name: section section_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.section
    ADD CONSTRAINT section_pkey PRIMARY KEY (id, license_id);


--
-- Name: upload_object_action upload_object_map_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.upload_object_action
    ADD CONSTRAINT upload_object_map_pkey PRIMARY KEY (id);


--
-- Name: variety variety_pkey; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.variety
    ADD CONSTRAINT variety_pkey PRIMARY KEY (id, license_id);


--
-- Name: vehicle vehicle_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.vehicle
    ADD CONSTRAINT vehicle_pkey PRIMARY KEY (id, license_id);
