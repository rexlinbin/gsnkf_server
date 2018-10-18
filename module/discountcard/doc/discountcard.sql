set names utf8;
create table if not exists t_discount_card(
	uid int unsigned not null comment "用户id",
	card_id int unsigned not null comment "优惠卡ID",
	buy_time int unsigned not null comment "优惠卡购买的时间",
	due_time int unsigned not null comment "优惠卡到期的时间",
    va_card_info blob not null comment "",
	primary key(uid, card_id)
)default charset utf8 engine = InnoDb;

