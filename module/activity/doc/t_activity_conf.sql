set names utf8;

CREATE TABLE IF NOT EXISTS t_activity_conf
(
    name            varchar(32) not null comment '配置名字',
    version         int unsigned not null comment '版本',
    start_time      int unsigned not null comment '开始时间',
    end_time        int unsigned not null comment '结束时间',   
    need_open_time  int unsigned not null comment '需要的开服时间',
    str_data        blob not null comment '原始配置数据，给前端用',
    va_data         blob not null comment '配置数据，已解析好的，后端使用的',
    
    primary key(name,version)
)engine = InnoDb default charset utf8;


