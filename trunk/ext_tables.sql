#
# Table structure for table 'sys_identity'
#
CREATE TABLE sys_identity (
	uid int(11) NOT NULL auto_increment,
	foreign_tablename varchar(255) NOT NULL DEFAULT '',
	foreign_uid int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid)
);