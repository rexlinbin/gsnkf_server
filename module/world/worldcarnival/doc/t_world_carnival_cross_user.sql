set names utf8;
create table if not exists t_world_carnival_cross_user
(
    server_id       int unsigned not null comment "所在服server_id",
    pid             int unsigned not null comment "玩家pid",
    rank            int unsigned not null comment "玩家排名",
    lose_times      int unsigned not null comment "玩家失败次数",
    update_time     int unsigned not null comment "更新时间",
    va_extra      blob not null comment '扩展信息战斗数据等，battle = array()',
    primary key(server_id, pid)
)default charset utf8 engine = InnoDb;
