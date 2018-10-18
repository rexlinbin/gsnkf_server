set names utf8;

create table if not exists t_desact
(
    uid           int unsigned not null comment 'uid',
    update_time   int unsigned not null comment '数据更新时间',
    va_data       blob not null comment '新类型福利活动个人数据 taskInfo=>(tid=>(num=>int,rewarded=>array))',
    
    primary key(uid)
)engine = InnoDb default charset utf8;