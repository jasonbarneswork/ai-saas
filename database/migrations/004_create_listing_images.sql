CREATE TABLE IF NOT EXISTS listing_images (
    id BIGSERIAL PRIMARY KEY,

    listing_id BIGINT NOT NULL
        REFERENCES listings(id)
        ON DELETE CASCADE,

    file_path VARCHAR(500) NOT NULL,

    original_filename VARCHAR(255),

    mime_type VARCHAR(100) NOT NULL,

    file_size BIGINT NOT NULL
        CHECK (file_size >= 0),

    sort_order INTEGER NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_listing_images_listing_id
    ON listing_images(listing_id);

CREATE INDEX idx_listing_images_sort_order
    ON listing_images(listing_id, sort_order);
