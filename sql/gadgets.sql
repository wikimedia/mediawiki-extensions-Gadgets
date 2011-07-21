-- Add gadgets table

CREATE TABLE /*_*/gadgets (
	-- Name of gadget. Cannot be changed, ever.
	gd_name varchar(255) binary NOT NULL PRIMARY KEY,
	-- JSON blob with gadget properties. See (TODO: fill this) for documentation on the format
	gd_blob mediumblob NOT NULL,
	-- Whether or not this gadget is global (called 'shared' in the interface)
	gd_global bool NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/gd_global_name ON /*_*/gadgets (gd_global, gd_name);
