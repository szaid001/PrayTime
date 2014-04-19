create table if not exists geolocation (
	id integer PRIMARY KEY,
	parent integer,
	code varchar(8),
	name varchar(48),
	nameutf varchar(64),
	path varchar(32),
	namespace varchar(128),
	namespaceutf varchar(192),
	latitude decimal(10,6), 
	longitude decimal(10,6),
	info text
) WITHOUT ROWID;
