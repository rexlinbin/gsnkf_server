set names utf8;
CREATE TABLE IF NOT EXISTS t_sevens_lottery
(
    uid int unsigned not null comment '用户id' ,
    num int unsigned not null comment '每日使用金币抽奖次数',
    point int unsigned not null comment '积分',
    lucky int unsigned not null comment '幸运值',
    free_time int unsigned not null comment '上次使用免费抽奖时间',
    refresh_time int unsigned not null comment '每日刷新时间',
    primary key(uid)
)engine = InnoDb default charset utf8;
