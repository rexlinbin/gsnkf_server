set names utf8;
create table if not exists t_hero_shop(
	uid int unsigned not null comment '用户id',
	score int unsigned not null comment '',
	score_time int unsigned not null comment '',
	free_cd int unsigned not null comment '',
	free_num int unsigned not null comment '',
	buy_num int unsigned not null comment '金币购买卡牌的次数',
	special_buy_num int unsigned not null comment '使用掉落表C的次数',
	reward_time int unsigned not null comment '发奖时间',
	primary key(uid),
	index score_score_time(score,score_time)
)engine = InnoDb default charset utf8;