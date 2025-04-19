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

CREATE TABLE validable_unique_models (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255)
);

CREATE TABLE jobs (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    perform_at TEXT NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    args TEXT NOT NULL DEFAULT '{}',
    frequency TEXT NOT NULL DEFAULT '',
    queue TEXT NOT NULL DEFAULT 'default',
    locked_at TEXT,
    number_attempts BIGINT NOT NULL DEFAULT 0,
    last_error TEXT NOT NULL DEFAULT '',
    failed_at TEXT
);
