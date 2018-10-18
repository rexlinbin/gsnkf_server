
set names utf8;

create table t_server( 
    server_id int unsigned not null comment "服务器ID",
    server_name varchar(64) not null comment "服务器名",
    db_name varchar(64) not null comment "数据库名",
    open_time int unsigned not null comment "服务器开服时间",
    primary key(server_id)
)default charset utf8 engine = InnoDb;