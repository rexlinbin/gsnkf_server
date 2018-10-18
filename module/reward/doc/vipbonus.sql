set names utf8;

create table t_vipbonus(
    uid int unsigned not null comment '用户ID',
    bonus_rece_time int unsigned not null comment '上次领取VIP每日福利的时间',
    va_info blob not null comment 'vip每周礼包week_gift{$vip=>$time}',
    primary key(uid)
)default charset utf8 engine = InnoDb;