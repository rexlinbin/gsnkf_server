set names utf8;

create table if not exists t_one_recharge( 
    uid            int unsigned not null comment '发送者uid',
    refresh_time   int unsigned not null comment '刷新时间,用于新活动刷新',
    if_remain	   int unsigned not null comment '是否剩余未领取的奖励,1代表剩余,0代表已领完',
    va_info        blob not null comment 'va_reward{$rewardId => $select:选取的奖励}',
    
    primary key(uid)
)engine = InnoDb default charset utf8;