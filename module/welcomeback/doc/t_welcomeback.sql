set names utf8;

create table if not exists t_welcomeback( 
    uid            int unsigned not null comment "玩家uid",
    offline_time   int unsigned not null comment "活动对应的玩家离线时间",
    back_time      int unsigned not null comment "玩家开启回归活动的时间",
    end_time       int unsigned not null comment "回归活动结束时间",
    need_bufa      int unsigned not null comment "活动结束后把玩家能领却未领的奖励补发到中心，0：不需要，1：需要。初始化1",
    va_info        blob not null comment "
			'gift' => array(
				id => gainGift					1:未领取礼包，2：已领取
			), 
			'task' => array(
				id => array(
						0 => finishedTimes, 	目前执行次数
						1 => status,			0:未完成任务，1：任务完成但还未领取奖励，2：已领取奖励
						2 => select				-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
				)
			), 
			'recharge' => array(
				id => array(
						0 => gold,				需要充值金币
						1 => rechargeTimes,		总的可充值次数
						2 => hadRewardTimes,	已领奖次数
						3 => toRewardTimes,		待领奖次数
						4 => array(
								select			-1：未领取，0：领取全部物品，1：领取第一个，2：领取第二个，以此类推
							 )	
				)
			),
			'rechargeUpdateTime' => rechargeUpdateTime,  单充更新时间
			'shop' => array(
				id => buyTimes					已购买次数
			)",
    
    primary key(uid)
)engine = InnoDb default charset utf8;