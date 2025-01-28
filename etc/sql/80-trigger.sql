
--
-- Name: log_delta_trigger(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.log_delta_trigger() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN

 CASE TG_OP
 WHEN 'UPDATE' THEN
  INSERT INTO log_delta (command, subject, subject_id, v0, v1) VALUES (TG_OP, TG_TABLE_NAME, OLD.id, row_to_json(OLD), row_to_json(NEW));
  RETURN NEW;
 WHEN 'INSERT' THEN
  INSERT INTO log_delta (command, subject, subject_id, v1) VALUES (TG_OP, TG_TABLE_NAME, NEW.id, row_to_json(NEW));
  RETURN NEW;
 WHEN 'DELETE' THEN
  INSERT INTO log_delta (command, subject, subject_id, v0) VALUES (TG_OP, TG_TABLE_NAME, OLD.id, row_to_json(OLD));
  RETURN OLD;
 END CASE;

END;
$$;


--
-- Name: b2b_incoming log_delta_b2b_incoming; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_b2b_incoming AFTER INSERT OR DELETE OR UPDATE ON public.b2b_incoming FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: b2b_outgoing log_delta_b2b_outgoing; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_b2b_outgoing AFTER INSERT OR DELETE OR UPDATE ON public.b2b_outgoing FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: b2c_sale log_delta_b2c_sale; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_b2c_sale AFTER INSERT OR DELETE OR UPDATE ON public.b2c_sale FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: company log_delta_company; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_company AFTER INSERT OR DELETE OR UPDATE ON public.company FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: contact log_delta_contact; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_contact AFTER INSERT OR DELETE OR UPDATE ON public.contact FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: crop log_delta_crop; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_crop AFTER INSERT OR DELETE OR UPDATE ON public.crop FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: disposal log_delta_disposal; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_disposal AFTER INSERT OR DELETE OR UPDATE ON public.disposal FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: inventory log_delta_inventory; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_inventory AFTER INSERT OR DELETE OR UPDATE ON public.inventory FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: inventory_adjust log_delta_inventory_adjust; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_inventory_adjust AFTER INSERT OR DELETE OR UPDATE ON public.inventory_adjust FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: lab_result log_delta_lab_result; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_lab_result AFTER INSERT OR DELETE OR UPDATE ON public.lab_result FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: license log_delta_license; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_license AFTER INSERT OR DELETE OR UPDATE ON public.license FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: product log_delta_product; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_product AFTER INSERT OR DELETE OR UPDATE ON public.product FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: section log_delta_section; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_section AFTER INSERT OR DELETE OR UPDATE ON public.section FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: variety log_delta_variety; Type: TRIGGER; Schema: public; Owner: openthc_bong
--

CREATE TRIGGER log_delta_variety AFTER INSERT OR DELETE OR UPDATE ON public.variety FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();


--
-- Name: vehicle log_delta_vehicle; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER log_delta_vehicle AFTER INSERT OR DELETE OR UPDATE ON public.vehicle FOR EACH ROW EXECUTE FUNCTION public.log_delta_trigger();
