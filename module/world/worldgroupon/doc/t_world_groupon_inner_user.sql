set names utf8;

create table if not exists t_world_groupon_inner_user
(
    uid int unsigned not null comment "uid",
    point int unsigned not null comment "团购积分",
    coupon int unsigned not null comment "团购券",
    optime int unsigned not null comment "参与活动时间--用来补发奖励,清积分，每日购买物品限制",
    reward_time int unsigned not null comment "补发金币差价的时间",
    va_info blob not null comment "
      his => array{
        array(
          good_id => int 物品id,
          num => int 团购数量,
          gold => int 本次花费金币,
          coupon => int 本次所得团购券,
          buy_time => int 时间,
        ),...
      },
      point_reward => array{
        rewardid,...
      },
    ",
    primary key(uid)
)default charset utf8 engine = InnoDb;