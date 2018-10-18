create table t_olympic_global(
  id int unsigned not null comment 'ID',
  silver_pool int unsigned not null comment '奖池',
  va_data blob not null comment 'last_campion => int, win_cont => int, avg_level_of_arean => int',
  primary key(id)
)default charset utf8 engine = InnoDb;