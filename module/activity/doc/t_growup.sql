set names utf8;

CREATE TABLE IF NOT EXISTS t_growup(
    uid int unsigned not null comment "用户ID",
    activation_time int unsigned not null comment "激活时刻",
    va_grow_up blob not null comment '包括 : already(玩家已经领取的列表)',
    primary key(uid)
)default charset utf8 engine = InnoDb;