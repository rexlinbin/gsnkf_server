set names utf8;

create table if not exists t_countrywar_cross_team    -- 加缓存TODO
(
		server_id               int unsigned not null comment '活动id ',                             
		team_id					int unsigned not null comment '分组id',
		update_time				int unsigned not null comment '更新时间',

    primary key(server_id)
)engine = InnoDb default charset utf8;
