
--
-- Name: b2b_incoming b2b_incoming_target_license_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: openthc_bong
--

ALTER TABLE ONLY public.b2b_incoming
    ADD CONSTRAINT b2b_incoming_target_license_id_fkey FOREIGN KEY (target_license_id) REFERENCES public.license(id);
