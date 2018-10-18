set names utf8;
create table if not exists t_groupon_user(
  uid int unsigned not null comment '用户id',
  buy_time int unsigned not null comment '最后一次购买时间',
  va_data blob not null comment 'array($goodid商品id=>array(rewardid奖励id=>state领取状态))',
  primary key(uid)
)engine = InnoDb default charset utf8;