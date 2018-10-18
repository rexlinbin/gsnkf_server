
set names utf8;

CREATE TABLE IF NOT EXISTS t_boss(

	boss_id			int(10) unsigned not null comment 'boss ID',
	level			int(10) unsigned not null comment 'boss 等级',
	hp				int(10) unsigned not null comment '当前血量',
	start_time		int(10) unsigned not null comment '开始时间',
	refresh_time 	int(10) unsigned not null comment '刷新时间',
	va_boss			blob not null comment '超级兵array()',
	
	primary key( boss_id )
)engine = InnoDb default charset utf8;