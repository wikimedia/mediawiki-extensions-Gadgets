-- Add gadgets table

CREATE TABLE /*_*/gadgets (
	-- Unique id of gadget. Cannot be changed, ever.
	gd_id varchar(255) binary NOT NULL PRIMARY KEY,
	-- JSON blob with gadget properties. See Gadget::__construct() for documentation on the format
	gd_blob mediumblob NOT NULL,
	-- Whether or not this gadget is allowed to be shared through a foreign repository
	gd_shared bool NOT NULL,
	-- The timestamp of when the metadata was last changed. Used for conflict detection and cache invalidation
	gd_timestamp binary(14) NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/gd_shared_id ON /*_*/gadgets (gd_shared, gd_id);
CREATE INDEX /*i*/gd_id_timestamp ON /*_*/gadgets (gd_id, gd_timestamp);

-- Table tracking .js and .css pages to make efficient prefix searches by extension possible
-- (used for AJAX autocompletion)
CREATE TABLE /*_*/gadgetpagelist (
	-- Extension of the page. Right now this can only be 'js' or 'css' but this may change in the future
	gpl_extension varchar(32) NOT NULL,
	-- Namespace
	gpl_namespace int NOT NULL,
	-- Page title
	gpl_title varchar(255) NOT NULL
) /*$wgDBTableOptions*/;
CREATE UNIQUE INDEX /*i*/gpl_namespace_title ON /*_*/gadgetpagelist (gpl_namespace, gpl_title);
CREATE INDEX /*i*/gpl_extension_namespace_title ON /*_*/gadgetpagelist (gpl_extension, gpl_namespace, gpl_title);
