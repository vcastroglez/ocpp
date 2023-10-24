<?php
require_once(__DIR__.'/src/config/define.php');
require_once(__DIR__.'/src/MySQL.php');

use MyApp\MySQL;

$db = new MySQL(DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME);


$query = "
	create table if not exists connections
(
    id         int auto_increment,
    created_at timestamp default CURRENT_TIMESTAMP not null,
    uuid       varchar(255)                        not null,
    constraint connections_pk
        primary key (id)
);
";

$db->query($query);
$db->closeConnection();;