set names utf8;

create table if not exists t_mission_cross_team
(
    server_id 		int unsigned not null comment '服server_id--缓存',
    team_id 		int unsigned not null comment '分组id',
    update_time 	int unsigned not null comment '更新时间',
    
    primary key(server_id)
)default charset utf8 engine = InnoDb;