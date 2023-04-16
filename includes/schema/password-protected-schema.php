<?php

$password_protected_schema = array();

$password_protected_schema['activity_logs'] = "
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    ip varchar(55) NOT NULL,
    browser text NOT NULL,
    status tinytext NOT NULL,
    created_at varchar(55) NOT NULL,
    PRIMARY KEY (id)";

$password_protected_schema['manage_passwords'] = "
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    password varchar(55) NOT NULL,
    encrypted_password varchar(55) NOT NULL,
    uses mediumint(9) NOT NULL,
    used mediumint(9) NOT NULL DEFAULT 0,
    expiry varchar(55) NOT NULL,
    status varchar(55) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)";

$password_protected_schema['limit_password'] = "
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    client_ip varchar(55) NOT NULL,
    password_attempts int(11) NOT NULL,
    attempt_at varchar(55) NOT NULL,
    locked_at VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)";

return $password_protected_schema;