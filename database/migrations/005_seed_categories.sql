INSERT INTO categories (name, slug, description)
VALUES
    ('Electronics', 'electronics', 'Computers, phones, televisions, cameras and other electronics.'),
    ('Vehicles', 'vehicles', 'Cars, trucks, motorcycles and other vehicles.'),
    ('Home & Garden', 'home-garden', 'Furniture, appliances, tools and garden equipment.'),
    ('Clothing', 'clothing', 'Clothing, shoes and accessories.'),
    ('Sports & Recreation', 'sports-recreation', 'Sports equipment, outdoor gear and recreational items.'),
    ('Collectibles', 'collectibles', 'Collectibles, antiques, memorabilia and other unique items.'),
    ('Books & Media', 'books-media', 'Books, movies, music, games and other media.'),
    ('Other', 'other', 'Items that do not fit another category.')
ON CONFLICT (slug) DO NOTHING;
