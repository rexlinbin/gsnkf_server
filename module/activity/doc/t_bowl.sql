set names utf8;
CREATE TABLE IF NOT EXISTS t_bowl
(
    uid		        int unsigned not null comment '用户id',
    update_time     int unsigned not null comment '更新时间',
    va_extra        blob not null comment "'type'(聚宝盆类型)=>array('btime'(聚宝时间),'reward'(已领奖励)=>array(day(天)))",
    primary key(uid)
)engine = InnoDb default charset utf8;