
CREATE VIEW b2b_incoming_full AS
	SELECT b2b_incoming.id
	  , b2b_incoming.source_license_id
	  , b2b_incoming.target_license_id
	  , b2b_incoming.created_at
	  , b2b_incoming.updated_at
	  , b2b_incoming.stat
	  , b2b_incoming.flag
	  , b2b_incoming.hash
	  , b2b_incoming.name
	  , b2b_incoming.data
	  , b2b_incoming_item.id              AS b2b_incoming_item_id
	  , b2b_incoming_item.flag            AS b2b_incoming_item_flag
	  , b2b_incoming_item.stat            AS b2b_incoming_item_stat
	  , b2b_incoming_item.created_at      AS b2b_incoming_item_created_at
	  , b2b_incoming_item.updated_at      AS b2b_incoming_item_updated_at
	  , b2b_incoming_item.hash            AS b2b_incoming_item_hash
	  , b2b_incoming_item.name            AS b2b_incoming_item_name
	  , b2b_incoming_item.data            AS b2b_incoming_item_data
	FROM b2b_incoming
	JOIN b2b_incoming_item ON b2b_incoming.id = b2b_incoming_item.b2b_incoming_id
	;

CREATE VIEW b2b_outgoing_full AS
	SELECT b2b_outgoing.id
	  , b2b_outgoing.source_license_id
	  , b2b_outgoing.target_license_id
	  , b2b_outgoing.created_at
	  , b2b_outgoing.updated_at
	  , b2b_outgoing.stat
	  , b2b_outgoing.flag
	  , b2b_outgoing.hash
	  , b2b_outgoing.name
	  , b2b_outgoing.data
	  , b2b_outgoing_item.id              AS b2b_outgoing_item_id
	  , b2b_outgoing_item.flag            AS b2b_outgoing_item_flag
	  , b2b_outgoing_item.stat            AS b2b_outgoing_item_stat
	  , b2b_outgoing_item.created_at      AS b2b_outgoing_item_created_at
	  , b2b_outgoing_item.updated_at      AS b2b_outgoing_item_updated_at
	  , b2b_outgoing_item.hash            AS b2b_outgoing_item_hash
	  , b2b_outgoing_item.name            AS b2b_outgoing_item_name
	  , b2b_outgoing_item.data            AS b2b_outgoing_item_data
	FROM b2b_outgoing
	JOIN b2b_outgoing_item ON b2b_outgoing.id = b2b_outgoing_item.b2b_outgoing_id
	;
