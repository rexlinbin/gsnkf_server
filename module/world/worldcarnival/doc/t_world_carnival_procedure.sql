set names utf8;
create table if not exists t_world_carnival_procedure
(
    session       int unsigned not null comment "届数",
    round         int unsigned not null comment "大轮次",
    status        int unsigned not null comment "大轮次状态",
    sub_round     int unsigned not null comment "小轮次",
    sub_status    int unsigned not null comment "小轮次状态",
    update_time   int unsigned not null comment "更新时间",
    va_extra      blob not null comment '扩展信息战报数据，各个小轮次结束时间等，record => array(),fight_time => array()',
    primary key(session, round)
)default charset utf8 engine = InnoDb;
