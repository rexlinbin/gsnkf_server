set names utf8;

create table if not exists t_charge_dart_user 
(
	uid int unsigned not null comment '玩家uid',
	cmp_time int unsigned not null comment '用于刷新的对比时间',
	shipping_num int unsigned not null comment '当日已用押镖运送次数',
	buy_shipping_num int unsigned not null comment '当日购买的押镖运送次数',
	rob_num int unsigned not null comment '当日已用的掠夺次数',
	buy_rob_num int unsigned not null comment '当日购买的掠夺次数',
	assistance_num int unsigned not null comment '当日已用的协助次数',
	buy_assistance_num int unsigned not null comment '当日购买的协助次数',
	refresh_num int unsigned not null comment '当日已用的刷新次数',
	stage_id int unsigned not null comment '区域id',
	stage_refresh_num int unsigned not null comment '当前区域刷新次数，暗格用',
	has_invited int unsigned not null comment '是否已经邀请了协助',
	assistance_uid int unsigned not null comment '当前协助人',
	begin_time int unsigned not null comment '开始时间',
	page_id int unsigned not null comment '所在页id',
	road_id int unsigned not null comment '所在路id',
	be_robbed_num int unsigned not null comment'被掠夺次数',
	user_have_rage int unsigned not	null comment '主人是否开启了狂怒buff',
	assistance_have_rage int unsigned not null comment '协助者是否开启了狂怒buff',
	tid int unsigned not null comment 'timer的id',

	primary key(uid),
	index(stage_id,page_id,road_id)

)default charset utf8 engine = InnoDb; 