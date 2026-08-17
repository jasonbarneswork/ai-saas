CREATE TABLE IF NOT EXISTS listings (
    id BIGSERIAL PRIMARY KEY,

    user_id BIGINT NOT NULL
        REFERENCES users(id)
        ON DELETE CASCADE,

    category_id BIGINT NOT NULL
        REFERENCES categories(id)
        ON DELETE RESTRICT,

    title VARCHAR(200) NOT NULL,

    description TEXT,

    price NUMERIC(12, 2) NOT NULL
        CHECK (price >= 0),

    currency CHAR(3) NOT NULL DEFAULT 'USD',

    condition VARCHAR(30) NOT NULL DEFAULT 'used',

    status VARCHAR(30) NOT NULL DEFAULT 'draft',

    published_at TIMESTAMP,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_listings_user_id
    ON listings(user_id);

CREATE INDEX idx_listings_category_id
    ON listings(category_id);

CREATE INDEX idx_listings_status
    ON listings(status);

CREATE INDEX idx_listings_created_at
    ON listings(created_at);

CREATE INDEX idx_listings_published_at
    ON listings(published_at);
