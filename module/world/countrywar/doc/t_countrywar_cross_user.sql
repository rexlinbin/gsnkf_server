set names utf8;

create table if not exists t_countrywar_cross_user
(
		pid                   	int unsigned not null comment 'pid',                             
		server_id				int unsigned not null comment 'server_id',
		uuid					int unsigned not null comment 'uuid--索引的问题',
		sign_time				int unsigned not null comment '报名时间',
		team_room_id			int unsigned not null comment '组房id',
		country_id				int unsigned not null comment '国家id',
		side					int unsigned not null comment '波',
		final_qualify			int unsigned not null comment '参加决赛的资格',
		uname 					char(20) not null comment '名字',
		htid             		int unsigned not null comment 'htid',	
		fight_force             int unsigned not null comment '战斗力',
		vip						int unsigned not null comment 'vip',
		level     	 			int unsigned not null comment '等级',
		fans_num             	int unsigned not null comment '热度',
		cocoin_num     	 		int unsigned not null comment '国战币',
		copoint_num				int unsigned not null comment '国战总积分,可消耗的',
		recover_percent			int unsigned not null comment '回血点',
		audition_point     	 	int unsigned not null comment '初赛积分', 
		audition_point_time     int unsigned not null comment '初赛积分时间',
		final_point     	 	int unsigned not null comment '决赛积分',
		final_point_time     	int unsigned not null comment '决赛积分时间',
		audition_inspire_num	int unsigned not null comment '初赛鼓舞次数',
		finaltion_inspire_num	int unsigned not null comment '决赛鼓舞次数',
		update_time				int unsigned not null comment '更新时间',
		va_extra				blob not null comment '扩展信息1.时装信息',

    primary key(pid,server_id),
    index team_room_id_sign_time_final_qualify(team_room_id,sign_time,final_qualify)
    
)engine = InnoDb default charset utf8;

