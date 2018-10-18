set names utf8;

create table if not exists t_countrywar_inner_worship
(
		war_id					int unsigned not null comment 'war_id',
		pid                   	int unsigned not null comment 'pid',                             
		server_id				int unsigned not null comment 'server_id',
		uname 					char(20) not null comment '名字',
		htid             		int unsigned not null comment 'htid',
		title             		int unsigned not null comment '玩家称号',
		fight_force             int unsigned not null comment '战斗力',
		vip						int unsigned not null comment 'vip',
		level     	 			int unsigned not null comment '等级',
		va_extra				blob not null comment '扩展信息1.时装信息',

    primary key(war_id)
)engine = InnoDb default charset utf8;
