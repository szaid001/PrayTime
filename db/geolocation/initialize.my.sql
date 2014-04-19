drop table if exists geolocation;
create table if not exists geolocation (
	id integer not null auto_increment,
	parent integer,
	code varchar(8),
	name varchar(48),
	nameutf varchar(64),
	path varchar(32),
	namespace varchar(128),
	namespaceutf varchar(192),
	latitude decimal(10,6), 
	longitude decimal(10,6),
	info text,
	primary key (id),
	key(path, code)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
