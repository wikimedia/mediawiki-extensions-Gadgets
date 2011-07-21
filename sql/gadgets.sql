-- Add gadgets table

CREATE TABLE /*_*/gadgets (
	gd_name varchar(255) binary NOT NULL PRIMARY KEY,
	gd_blob mediumblob NOT NULL,
	gd_global bool NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/gd_global_name ON /*_*/gadgets (gd_global, gd_name);
