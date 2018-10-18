set names utf8;

create table if not exists t_mineral_guards
(
  uid int unsigned not null comment '守卫军uid',
  domain_id int unsigned not null comment '资源区ID',
  pit_id int unsigned not null comment '矿坑id',
  guard_time int unsigned not null comment '成为守卫军的时间',
  due_timer int unsigned not null comment '守卫军timer',
  status tinyint unsigned not null comment '状态值 0 非守卫军 1 守卫军',
  primary key(uid),
  key pit(domain_id, pit_id)
)engine = InnoDb default charset utf8;
