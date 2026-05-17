CREATE TABLE IF NOT EXISTS cashiers (
    id BIGSERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    pin TEXT NOT NULL DEFAULT '0000',
    role TEXT DEFAULT 'cashier',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

ALTER TABLE cashiers ENABLE ROW LEVEL SECURITY;

CREATE POLICY "anon select cashiers" ON cashiers FOR SELECT USING (true);
CREATE POLICY "anon insert cashiers" ON cashiers FOR INSERT WITH CHECK (true);
CREATE POLICY "anon update cashiers" ON cashiers FOR UPDATE USING (true);
CREATE POLICY "anon delete cashiers" ON cashiers FOR DELETE USING (true);

-- Default cashier
INSERT INTO cashiers (name, pin, role) VALUES ('Admin', '1234', 'admin');
