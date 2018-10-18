set names utf8;

create table if not exists t_new_server_goods(
    day int unsigned not null comment '开服7天狂欢抢购商品的第几天',
    buy_num int unsigned not null comment '当天对应商品的购买数量',
    primary key(day) 
)default charset utf8 engine = InnoDb;