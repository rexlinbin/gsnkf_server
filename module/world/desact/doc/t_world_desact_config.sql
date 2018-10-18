set names utf8;

create table if not exists t_world_desact_config
(
    sess           int unsigned not null comment 'id',
    update_time    int unsigned not null comment '数据更新时间',
    version        int unsigned not null comment '配置的版本号',
    va_config      blob not null comment '当前所需配置 config=>id=>array(id=>int,last_day=>int,reward=>array(),desc=>string,name=>string,tip=>string)',
    
    primary key(sess)
)engine = InnoDb default charset utf8;