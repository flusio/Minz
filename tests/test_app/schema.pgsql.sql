CREATE TABLE friends (
    id serial NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    address varchar(255)
);

CREATE TABLE rabbits (
    rabbit_id serial NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    friend_id integer NOT NULL,
    FOREIGN KEY (friend_id) REFERENCES friends(id)
);
