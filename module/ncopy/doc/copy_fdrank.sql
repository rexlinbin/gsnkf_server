set names utf8;


CREATE TABLE IF NOT EXISTS t_copy_fdrank( 
    uid int unsigned not null comment "玩家ID",
    level int unsigned not null comment "玩家败部队时刻的等级",
    copy_id int unsigned not null comment "副本ID",
    rank int unsigned not null comment "名次",
    primary key(copy_id,uid)
)default charset utf8 engine = InnoDb;