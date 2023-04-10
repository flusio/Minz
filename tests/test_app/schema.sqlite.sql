CREATE TABLE friends (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    created_at TEXT,
    updated_at TEXT,
    is_kind BOOLEAN DEFAULT true,
    options TEXT NOT NULL DEFAULT '[]'
);

CREATE TABLE rabbits (
    id VARCHAR(16) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    friend_id INTEGER NOT NULL,
    FOREIGN KEY (friend_id) REFERENCES friends(id)
);
