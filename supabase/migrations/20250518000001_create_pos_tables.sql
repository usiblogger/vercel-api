CREATE TABLE IF NOT EXISTS products (
    id BIGSERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    category TEXT DEFAULT '',
    image_url TEXT DEFAULT '',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS orders (
    id BIGSERIAL PRIMARY KEY,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method TEXT NOT NULL DEFAULT 'cash',
    items JSONB DEFAULT '[]',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

CREATE POLICY "anon select products" ON products FOR SELECT USING (true);
CREATE POLICY "anon insert products" ON products FOR INSERT WITH CHECK (true);
CREATE POLICY "anon update products" ON products FOR UPDATE USING (true);
CREATE POLICY "anon delete products" ON products FOR DELETE USING (true);

CREATE POLICY "anon select orders" ON orders FOR SELECT USING (true);
CREATE POLICY "anon insert orders" ON orders FOR INSERT WITH CHECK (true);
CREATE POLICY "anon delete orders" ON orders FOR DELETE USING (true);
