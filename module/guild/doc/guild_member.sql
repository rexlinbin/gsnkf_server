set names utf8;

create table if not exists t_guild_member
(
	uid 			int unsigned not null comment '用户uid',
	guild_id 		int unsigned not null comment '军团id',
	member_type 	tinyint unsigned not null comment '成员类型,0平民,1军团长,2副军团长',
	contri_point	int unsigned not null comment '贡献值',
	contri_total 	int unsigned not null comment '总贡献值',
	contri_week		int unsigned not null comment '本周贡献值，每周重置',
	last_contri_week int unsigned not null comment '上周贡献值，每周重置',
	contri_num		int unsigned not null comment '贡献次数，每日重置',
	contri_time		int unsigned not null comment '贡献时间，作为刷新时间',
	reward_time 	int unsigned not null comment '领奖时间',
	reward_buy_num	int unsigned not null comment '奖励购买次数，每日重置',
	reward_buy_time int unsigned not null comment '奖励购买时间',
	lottery_num		tinyint unsigned not null comment '摇奖次数，每日重置',
	lottery_time 	int unsigned not null comment '摇奖时间',
	grain_num		int unsigned not null comment '粮草数量',
	merit_num		int unsigned not null comment '功勋值',
	zg_num			int unsigned not null comment '战功值',
	refresh_num 	int unsigned not null comment '粮田刷新次数，每日重置',
	rejoin_cd		int	unsigned not null comment '再次加入的冷却时间',
	playwith_time 	int unsigned not null comment '切磋或被切磋时间',
	playwith_num  	tinyint unsigned not null comment '切磋次数，每日重置',
	be_playwith_num tinyint unsigned not null comment '被切磋次数，每日重置',
	va_member		blob not null comment '成员信息,fields=>{$id(粮田id)=>{$num(剩余采集次数),$time(刷新时间)}},skills=>{$id(技能)=>$level}',
	primary key(uid),
	key guild_id(guild_id)
)engine = InnoDb default charset utf8;