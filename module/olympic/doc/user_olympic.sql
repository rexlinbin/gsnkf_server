create table t_user_olympic( 
    uid int unsigned not null comment "用户ID",
    challenge_time int unsigned not null comment "挑战时间",
    challenge_cdtime int unsigned not null comment "挑战冷却时间",
    integral int unsigned not null comment "当前拥有的积分",
    cheer_uid int unsigned not null comment "助威对象",
    cheer_rfr_time int unsigned not null comment "助威次数刷新时刻,每天根据此时间更新助威玩家",
    cheer_num int unsigned not null comment "本大轮助威次数",
    be_cheer_num int unsigned not null comment "本大轮被助威次数",
    cheer_valid_num int unsigned not null comment "成功助威次数",
    win_accum_num int unsigned not null comment "连续胜利次数",
    weekly_rfr_time int unsigned not null comment "根据这个时间进行每周重置，重置cheer_num、cheer_valid_num、win_accum_num",
    va_olympic blob not null comment '存储积分获取方式和时间、个人战报',
    status tinyint unsigned not null comment "数据是否已经被删除， 0 被删除， 1 正常",
    primary key(uid)
)default charset utf8 engine = InnoDb;
