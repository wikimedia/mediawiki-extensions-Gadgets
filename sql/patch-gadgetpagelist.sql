CREATE TABLE /*_*/gadgetpagelist (
	gpl_extension varchar(32) NOT NULL,
	gpl_namespace int NOT NULL,
	gpl_title varchar(255) NOT NULL
) /*$wgDBTableOptions*/;
CREATE UNIQUE INDEX /*i*/gpl_namespace_title ON /*_*/gadgetpagelist (gpl_namespace, gpl_title);
CREATE INDEX /*i*/gpl_extension_namespace_title ON /*_*/gadgetpagelist (gpl_extension, gpl_namespace, gpl_title);
