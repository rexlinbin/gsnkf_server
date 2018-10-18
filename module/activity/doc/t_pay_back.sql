set names utf8;
create table if not exists t_pay_back
(
    uid            int unsigned  not null comment '用户id',
    rfr_time       int unsigned  not null comment '刷新时间',
    va_data        blob          not null comment '领奖记录  rewarded => rid => time ',
    
    primary key(uid)
)engine = InnoDb default charset utf8;