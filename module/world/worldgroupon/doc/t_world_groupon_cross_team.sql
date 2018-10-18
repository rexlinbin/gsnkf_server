set names utf8;

create table if not exists t_world_groupon_cross_team
(
    server_id int unsigned not null comment "服server_id",
    team_id int unsigned not null comment "分组id",
    update_time int unsigned not null comment "更新时间",
    primary key(server_id)
)default charset utf8 engine = InnoDb;