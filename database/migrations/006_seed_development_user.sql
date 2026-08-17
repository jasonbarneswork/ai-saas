INSERT INTO users (
    email,
    password_hash,
    first_name,
    last_name
)
VALUES (
    'demo@example.com',
    'development-only-password',
    'Demo',
    'User'
)
ON CONFLICT (email) DO NOTHING;
