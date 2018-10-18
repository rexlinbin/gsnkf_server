set names utf8;
create table if not exists t_dress_room
(
  uid int unsigned not null comment '用户id',
  va_data blob not null comment 'arr_dress => array(itemTmpid => array(as => int, gs => int), curDress => itemTmpid)',
  primary key(uid)
)engine = InnoDb default charset utf8;