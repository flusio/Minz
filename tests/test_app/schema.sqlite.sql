CREATE TABLE friends (
    id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    name varchar(255) NOT NULL,
    address varchar(255)
);

CREATE TABLE rabbits (
    rabbit_id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
    name varchar(255) NOT NULL,
    friend_id integer NOT NULL,
    FOREIGN KEY (friend_id) REFERENCES friends(id)
);
