set names utf8;

create table if not exists t_world_groupon_cross_info
(
    team_id int unsigned not null comment "分组id",
    good_id int unsigned not null comment "团购商品id",
    good_num int unsigned not null comment "商品团购数量",
    forge_num int unsigned not null comment "团购商品造假数量",
    upd_time int unsigned not null comment "更新时间",
    primary key(team_id, good_id)
)default charset utf8 engine = InnoDb;