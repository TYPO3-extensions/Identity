#
# Table structure for table 'sys_uuid'
#

CREATE TABLE sys_uuid (
  uid int(11) NOT NULL auto_increment,
  uuid varchar(36) NOT NULL default '',
  foreign_tablename varchar(255) NOT NULL default '',
  foreign_uid int(11) DEFAULT '0' NOT NULL,
  
  PRIMARY KEY (uid),
  UNIQUE KEY uuid (uuid,foreign_tablename,foreign_uid)
);
