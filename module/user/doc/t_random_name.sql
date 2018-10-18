set names utf8;

CREATE TABLE IF NOT EXISTS t_random_name(
	id int unsigned not null auto_increment comment '名字id',
	name varchar(16) not null unique comment '名字',
	status tinyint unsigned not null default 0 comment '状态，0：可用，1：已经被使用',
	gender tinyint unsigned not null default 0 comment '0：女, 1:男',
	primary key(id)
)engine = MyISAM default charset utf8;
