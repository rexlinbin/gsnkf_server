set names utf8;
create table if not exists t_topup_reward(
  uid int unsigned not null comment '用户id',
  va_data blob not null comment 'array(1[活动天数]=>array(1[是否可领取奖励], 0[是否已领取奖励]), 2=>array(0, 1), ...)',
  primary key(uid)
)engine = InnoDb default charset utf8;