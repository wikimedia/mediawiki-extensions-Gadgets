-- Add gadgets table

CREATE TABLE /*_*/gadgets (
	-- Name of gadget. Cannot be changed, ever.
	gd_name varchar(255) binary NOT NULL PRIMARY KEY,
	-- JSON blob with gadget properties. See Gadget::__construct() for documentation on the format
	gd_blob mediumblob NOT NULL,
	-- Whether or not this gadget is allowed to be shared through a foreign repository
	gd_shared bool NOT NULL,
	-- The timestamp of when the metadata was last changed. Used for conflict detection and cache invalidation
	gd_timestamp binary(14) NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/gd_shared_name ON /*_*/gadgets (gd_shared, gd_name);
CREATE INDEX /*i*/gd_name_timestamp ON /*_*/gadgets (gd_name, gd_timestamp);
