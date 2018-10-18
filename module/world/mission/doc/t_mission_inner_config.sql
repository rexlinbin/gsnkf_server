set names utf8;
create table if not exists t_mission_inner_config
(
		sess 		int unsigned not null comment 'uid',
		update_time int unsigned not null comment '更新时间',
        va_missconfig 	blob not null comment '在活动衔接问题中需要用到的配置，如排行奖励',
		
        primary key(sess)
)engine = InnoDb default charset utf8;
