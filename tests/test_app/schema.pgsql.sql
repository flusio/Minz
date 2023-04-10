CREATE TABLE friends (
    id SERIAL NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    created_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ,
    is_kind BOOLEAN DEFAULT true,
    options JSON DEFAULT '[]'
);

CREATE TABLE rabbits (
    id TEXT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    friend_id INTEGER NOT NULL,
    FOREIGN KEY (friend_id) REFERENCES friends(id)
);
