/* fim: [webform] new database tables. */

CREATE TABLE webform_types (
  form_id int(11) NOT NULL auto_increment,
  lm_obj_id int(11) NOT NULL default '0',
  form_name varchar(255) NOT NULL default '',
  dataset_id varchar(255) NOT NULL default '0',
  title varchar(255) default '',
  path varchar(255) default NULL,
  send_maxdate datetime default NULL,
  solution_ref varchar(255) default NULL,
  solution_mode enum('send','checked','date') NULL default 'checked',
  solution_date datetime default NULL,
  forum varchar(255) default NULL,
  forum_parent varchar(255) default NULL,
  forum_subject varchar(255) default NULL,
  PRIMARY KEY  (form_id)
) TYPE=MyISAM;

CREATE TABLE webform_savings (
  save_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  form_id int(11) NOT NULL default '0',
  dataset_id varchar(255) NOT NULL default '0',
  savedate datetime NOT NULL default '0000-00-00 00:00:00',
  senddate datetime default NULL,
  checkdate datetime default NULL,
  is_forum_saving tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (save_id)
) TYPE=MyISAM;

CREATE TABLE webform_entries (
  entry_id int(11) NOT NULL auto_increment,
  save_id int(11) NOT NULL default '0',
  fieldname varchar(255) NOT NULL default '',
  fieldvalue text,
  PRIMARY KEY  (entry_id)
) TYPE=MyISAM;
