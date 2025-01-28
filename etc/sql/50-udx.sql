
--
-- Name: license license_code_key; Type: CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.license
    ADD CONSTRAINT license_code_key UNIQUE (code);
