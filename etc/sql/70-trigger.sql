
CREATE FUNCTION log_delta_trigger() RETURNS trigger AS
$FUNC$
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
$FUNC$
LANGUAGE 'plpgsql' SECURITY DEFINER;


CREATE TRIGGER log_delta_b2b_incoming
	AFTER INSERT OR UPDATE OR DELETE
	ON b2b_incoming
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_b2b_incoming_item
	AFTER INSERT OR UPDATE OR DELETE
	ON b2b_incoming_item
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_b2b_outgoing
	AFTER INSERT OR UPDATE OR DELETE
	ON b2b_outgoing
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_b2b_outgoing_file
	AFTER INSERT OR UPDATE OR DELETE
	ON b2b_outgoing_file
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_b2b_outgoing_item
	AFTER INSERT OR UPDATE OR DELETE
	ON b2b_outgoing_item
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_b2c_sale
	AFTER INSERT OR UPDATE OR DELETE
	ON b2c_sale
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_b2c_sale_item
	AFTER INSERT OR UPDATE OR DELETE
	ON b2c_sale
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_company
	AFTER INSERT OR UPDATE OR DELETE
	ON company
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_crop
	AFTER INSERT OR UPDATE OR DELETE
	ON crop
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_lab_result
	AFTER INSERT OR UPDATE OR DELETE
	ON lab_result
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_license
	AFTER INSERT OR UPDATE OR DELETE
	ON license
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_lot
	AFTER INSERT OR UPDATE OR DELETE
	ON lot
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_lot_delta
	AFTER INSERT OR UPDATE OR DELETE
	ON lot_delta
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_product
	AFTER INSERT OR UPDATE OR DELETE
	ON product
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_section
	AFTER INSERT OR UPDATE OR DELETE
	ON section
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

CREATE TRIGGER log_delta_variety
	AFTER INSERT OR UPDATE OR DELETE
	ON variety
	FOR EACH ROW
	EXECUTE PROCEDURE log_delta_trigger();

-- CREATE TRIGGER log_delta_vehicle
-- 	AFTER INSERT OR UPDATE OR DELETE
-- 	ON vehicle
-- 	FOR EACH ROW
-- 	EXECUTE PROCEDURE log_delta_trigger();
