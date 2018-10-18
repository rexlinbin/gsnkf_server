set names utf8;
CREATE TABLE IF NOT EXISTS t_base_fdrank( 
    uid int unsigned not null comment "玩家ID",
    level int unsigned not null comment "玩家打败部队时刻的等级",
    base_id int unsigned not null comment "据点ID",
    base_level tinyint unsigned not null comment "据点难度级别",
    rank int unsigned not null comment "名次",
    va_fight_record blob not null comment "fd录像",
    primary key(base_id, base_level, uid)
)default charset utf8 engine = InnoDb;
