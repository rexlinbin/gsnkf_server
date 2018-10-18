set names utf8;

update t_tower set reset_hell = 1, can_fail_hell = 2 where uid != 0;
