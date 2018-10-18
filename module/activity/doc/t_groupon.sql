set names utf8;
create table if not exists t_groupon(
  id int unsigned not null default 0 comment '活动id',
  va_data blob not null comment 'array(goodlist=>(array{$goodid(商品id) => num(售出数量)}, $refreshtime(刷新时间)))',
  primary key(id)
)engine = InnoDb default charset utf8;