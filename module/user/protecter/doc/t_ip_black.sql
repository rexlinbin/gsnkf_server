
set names utf8;
create table if not exists t_ip_black(
    ip          int unsigned not null comment 'ip',
    server_id   int unsigned not null comment '服id',
    by_rule     int unsigned not null comment '规则',
    valid_time  int unsigned not null comment '有效时间',
    weight      int unsigned not null comment '权重',
    primary key(ip),
    index valid_time(valid_time)
)engine = InnoDb default charset utf8;