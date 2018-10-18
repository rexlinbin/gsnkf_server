set names utf8;
create table if not exists t_guild_copy_user
(
	uid int unsigned not null comment '玩家uid，主键',
	atk_damage int unsigned not null comment '今天玩家军团副本的总伤害',
	atk_damage_last int unsigned not null comment '昨天玩家军团副本的总伤害',
	atk_num int unsigned not null comment '今天总的可以攻击的次数',
	buy_num int unsigned not null comment '今天购买攻击的次数',
	update_time int unsigned not null comment '更新时间',
	recv_pass_reward_time int unsigned not null comment '通关后，领取阳光普照奖的时间',
	recv_box_reward_time int unsigned not null comment '通关后，领取宝箱奖励的时间',
	recv_rank_reward_time int unsigned not null comment '领取今天全服排行奖励的时间',
	refresh_time int unsigned not null comment '玩家上次点击全团突击的时间',
	va_extra blob not null comment '
                                   damage => array       玩家对据点伤害的详细信息
                                   [
                                       base_id => int
                                   ]
                                   ',
                                   
    atk_boss_num int unsigned not null default 0 comment '今天进攻boss的次数',
    buy_boss_num int unsigned not null default 0 comment '今天购买的次数',
	primary key(uid),
    index(update_time)
)engine = InnoDb default charset utf8;