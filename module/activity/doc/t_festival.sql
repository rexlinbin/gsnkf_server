set names utf8;

create table if not exists t_festival
(
    uid            int unsigned not null comment '用户id',
    update_time    int unsigned not null comment '最后一次修改数据时间',
    va_data        blob         not null comment '此次活动中已购买数量   hasBuy: fNumber => num',
    
    primary key(uid) 
)engine = InnoDb default charset utf8;