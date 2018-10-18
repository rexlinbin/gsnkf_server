set names utf8;
create table if not exists t_arena_fmt
(
  uid           int(10) unsigned not null comment '用户id',
  type          int(10) unsigned not null comment '类型id',
  update_time   int(10) unsigned not null comment '更新时间',
  fight_force   int(10) unsigned not null comment '战斗力',
  va_fmt        blob not null comment '战斗信息',
  primary key(uid,type)
)engine = InnoDb default charset utf8;
