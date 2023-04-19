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

CREATE TABLE jobs (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL,
    perform_at TIMESTAMPTZ NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    args JSON NOT NULL DEFAULT '{}',
    frequency TEXT NOT NULL DEFAULT '',
    queue TEXT NOT NULL DEFAULT 'default',
    locked_at TIMESTAMPTZ,
    number_attempts BIGINT NOT NULL DEFAULT 0,
    last_error TEXT NOT NULL DEFAULT '',
    failed_at TIMESTAMPTZ
);
