-- ============================================================
-- Yogitech database - Oracle DDL for SQL Developer Data Modeler
-- Relationships (foreign keys) are named <parent>_<verb>_<child>, e.g. buyers_place_orders.
-- Converted from yogitech.sql (MySQL/MariaDB dump, through migration 013).
-- Schema only: the demo data rows are not included.
-- Import: File > Import > DDL File > choose this file > Oracle Database.
-- Then Engineer to Logical Model for the Barker-notation diagram.
-- ============================================================

CREATE TABLE cart_items (
    id                   NUMBER(10) NOT NULL,
    user_id              NUMBER(10) NOT NULL,
    product_id           NUMBER(10) NOT NULL,
    quantity             NUMBER(5) DEFAULT 1 NOT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT cart_items_pk PRIMARY KEY (id),
    CONSTRAINT uq_cart_user_product UNIQUE (user_id, product_id)
);

CREATE TABLE categories (
    id                   NUMBER(10) NOT NULL,
    name                 VARCHAR2(80 CHAR) NOT NULL,
    slug                 VARCHAR2(80 CHAR) NOT NULL,
    api_slug             VARCHAR2(80 CHAR),
    icon                 VARCHAR2(30 CHAR) DEFAULT 'chip' NOT NULL,
    description          VARCHAR2(255 CHAR),
    sort_order           NUMBER(3) DEFAULT 0 NOT NULL,
    CONSTRAINT categories_pk PRIMARY KEY (id),
    CONSTRAINT categories_slug_uk UNIQUE (slug)
);

CREATE TABLE email_logs (
    id                   NUMBER(10) NOT NULL,
    user_id              NUMBER(10),
    order_id             NUMBER(10),
    to_email             VARCHAR2(190 CHAR) NOT NULL,
    subject              VARCHAR2(255 CHAR) NOT NULL,
    type                 VARCHAR2(40 CHAR) NOT NULL,
    driver               VARCHAR2(20 CHAR) NOT NULL,
    status               VARCHAR2(6 CHAR) NOT NULL,
    error                VARCHAR2(500 CHAR),
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT email_logs_pk PRIMARY KEY (id),
    CONSTRAINT email_logs_status_ck CHECK (status IN ('sent', 'failed', 'logged'))
);

CREATE TABLE migrations (
    id                   NUMBER(11) NOT NULL,
    migration            VARCHAR2(190 CHAR) NOT NULL,
    ran_at               TIMESTAMP NOT NULL,
    CONSTRAINT migrations_pk PRIMARY KEY (id),
    CONSTRAINT migrations_migration_uk UNIQUE (migration)
);

CREATE TABLE order_items (
    id                   NUMBER(10) NOT NULL,
    order_id             NUMBER(10) NOT NULL,
    product_id           NUMBER(10),
    product_name         VARCHAR2(180 CHAR) NOT NULL,
    thumbnail            VARCHAR2(500 CHAR),
    unit_price           NUMBER(12,2) NOT NULL,
    quantity             NUMBER(5) NOT NULL,
    line_total           NUMBER(12,2) NOT NULL,
    CONSTRAINT order_items_pk PRIMARY KEY (id)
);

CREATE TABLE orders (
    id                   NUMBER(10) NOT NULL,
    order_number         VARCHAR2(30 CHAR) NOT NULL,
    tracking_number      VARCHAR2(30 CHAR) NOT NULL,
    checkout_ref         VARCHAR2(30 CHAR) NOT NULL,
    buyer_id             NUMBER(10) NOT NULL,
    seller_id            NUMBER(10) NOT NULL,
    status               VARCHAR2(16 CHAR) DEFAULT 'pending' NOT NULL,
    payment_method       VARCHAR2(5 CHAR) DEFAULT 'cod' NOT NULL,
    payment_status       VARCHAR2(8 CHAR) DEFAULT 'unpaid' NOT NULL,
    subtotal             NUMBER(12,2) NOT NULL,
    shipping_fee         NUMBER(10,2) DEFAULT 0.00 NOT NULL,
    discount             NUMBER(10,2) DEFAULT 0.00 NOT NULL,
    total                NUMBER(12,2) NOT NULL,
    voucher_code         VARCHAR2(30 CHAR),
    recipient_name       VARCHAR2(120 CHAR) NOT NULL,
    phone                VARCHAR2(20 CHAR) NOT NULL,
    address_line         VARCHAR2(255 CHAR) NOT NULL,
    city                 VARCHAR2(100 CHAR) NOT NULL,
    province             VARCHAR2(100 CHAR) NOT NULL,
    region               VARCHAR2(8 CHAR) NOT NULL,
    postal_code          VARCHAR2(10 CHAR),
    notes                VARCHAR2(255 CHAR),
    courier              VARCHAR2(60 CHAR),
    estimated_from       DATE,
    estimated_to         DATE,
    shipped_at           TIMESTAMP,
    delivered_at         TIMESTAMP,
    cancelled_at         TIMESTAMP,
    cancel_reason        VARCHAR2(255 CHAR),
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT orders_pk PRIMARY KEY (id),
    CONSTRAINT orders_tracking_number_uk UNIQUE (tracking_number),
    CONSTRAINT orders_order_number_uk UNIQUE (order_number),
    CONSTRAINT orders_status_ck CHECK (status IN ('pending', 'confirmed', 'packed', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled')),
    CONSTRAINT orders_payment_method_ck CHECK (payment_method IN ('cod', 'gcash', 'card')),
    CONSTRAINT orders_payment_status_ck CHECK (payment_status IN ('unpaid', 'paid', 'refunded')),
    CONSTRAINT orders_region_ck CHECK (region IN ('NCR', 'Luzon', 'Visayas', 'Mindanao'))
);

CREATE TABLE password_resets (
    id                   NUMBER(10) NOT NULL,
    user_id              NUMBER(10) NOT NULL,
    token_hash           CHAR(64 CHAR) NOT NULL,
    expires_at           TIMESTAMP NOT NULL,
    used_at              TIMESTAMP,
    ip                   VARCHAR2(45 CHAR),
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT password_resets_pk PRIMARY KEY (id),
    CONSTRAINT password_resets_token_hash_uk UNIQUE (token_hash)
);

CREATE TABLE payments (
    id                   NUMBER(10) NOT NULL,
    public_id            CHAR(24 CHAR) NOT NULL,
    checkout_ref         VARCHAR2(30 CHAR) NOT NULL,
    buyer_id             NUMBER(10) NOT NULL,
    gateway              VARCHAR2(20 CHAR) NOT NULL,
    method               VARCHAR2(5 CHAR) NOT NULL,
    amount               NUMBER(12,2) NOT NULL,
    status               VARCHAR2(10 CHAR) DEFAULT 'pending' NOT NULL,
    gateway_ref          VARCHAR2(120 CHAR),
    payer                VARCHAR2(60 CHAR),
    failure              VARCHAR2(255 CHAR),
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    paid_at              TIMESTAMP,
    CONSTRAINT payments_pk PRIMARY KEY (id),
    CONSTRAINT payments_public_id_uk UNIQUE (public_id),
    CONSTRAINT payments_method_ck CHECK (method IN ('gcash', 'card')),
    CONSTRAINT payments_status_ck CHECK (status IN ('pending', 'authorized', 'paid', 'failed', 'cancelled'))
);

CREATE TABLE products (
    id                   NUMBER(10) NOT NULL,
    seller_id            NUMBER(10) NOT NULL,
    category_id          NUMBER(10) NOT NULL,
    external_id          NUMBER(10),
    source               VARCHAR2(9 CHAR) DEFAULT 'manual' NOT NULL,
    sku                  VARCHAR2(60 CHAR),
    name                 VARCHAR2(180 CHAR) NOT NULL,
    brand                VARCHAR2(80 CHAR),
    description          CLOB NOT NULL,
    price                NUMBER(12,2) NOT NULL,
    compare_price        NUMBER(12,2),
    api_price_usd        NUMBER(10,2),
    stock                NUMBER(10) DEFAULT 0 NOT NULL,
    thumbnail            VARCHAR2(500 CHAR),
    images               CLOB,
    video_url            VARCHAR2(500 CHAR),
    warranty             VARCHAR2(120 CHAR),
    return_policy        VARCHAR2(120 CHAR),
    specs                CLOB,
    rating               NUMBER(3,2) DEFAULT 0.00 NOT NULL,
    rating_count         NUMBER(10) DEFAULT 0 NOT NULL,
    sold_count           NUMBER(10) DEFAULT 0 NOT NULL,
    status               VARCHAR2(6 CHAR) DEFAULT 'active' NOT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT products_pk PRIMARY KEY (id),
    CONSTRAINT uq_products_seller_external UNIQUE (seller_id, external_id),
    CONSTRAINT products_source_ck CHECK (source IN ('manual', 'dummyjson')),
    CONSTRAINT products_status_ck CHECK (status IN ('active', 'draft'))
);

CREATE TABLE reviews (
    id                   NUMBER(10) NOT NULL,
    product_id           NUMBER(10) NOT NULL,
    user_id              NUMBER(10),
    order_id             NUMBER(10),
    reviewer_name        VARCHAR2(120 CHAR) NOT NULL,
    rating               NUMBER(3) NOT NULL,
    "COMMENT"            VARCHAR2(1000 CHAR) NOT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT reviews_pk PRIMARY KEY (id),
    CONSTRAINT uq_review_user_product UNIQUE (user_id, product_id)
);

CREATE TABLE shipment_events (
    id                   NUMBER(10) NOT NULL,
    order_id             NUMBER(10) NOT NULL,
    status               VARCHAR2(30 CHAR) NOT NULL,
    title                VARCHAR2(120 CHAR) NOT NULL,
    description          VARCHAR2(255 CHAR),
    location             VARCHAR2(120 CHAR),
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT shipment_events_pk PRIMARY KEY (id)
);

CREATE TABLE users (
    id                   NUMBER(10) NOT NULL,
    role                 VARCHAR2(6 CHAR) DEFAULT 'buyer' NOT NULL,
    name                 VARCHAR2(120 CHAR) NOT NULL,
    email                VARCHAR2(190 CHAR) NOT NULL,
    password             VARCHAR2(255 CHAR) NOT NULL,
    auth_version         NUMBER(10) DEFAULT 1 NOT NULL,
    phone                VARCHAR2(20 CHAR),
    store_name           VARCHAR2(120 CHAR),
    store_description    VARCHAR2(500 CHAR),
    address_line         VARCHAR2(255 CHAR),
    city                 VARCHAR2(100 CHAR),
    province             VARCHAR2(100 CHAR),
    region               VARCHAR2(8 CHAR),
    postal_code          VARCHAR2(10 CHAR),
    last_login_at        TIMESTAMP,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT users_pk PRIMARY KEY (id),
    CONSTRAINT users_email_uk UNIQUE (email),
    CONSTRAINT users_role_ck CHECK (role IN ('buyer', 'seller')),
    CONSTRAINT users_region_ck CHECK (region IN ('NCR', 'Luzon', 'Visayas', 'Mindanao'))
);

CREATE TABLE voucher_redemptions (
    id                   NUMBER(10) NOT NULL,
    voucher_id           NUMBER(10) NOT NULL,
    user_id              NUMBER(10) NOT NULL,
    checkout_ref         VARCHAR2(30 CHAR) NOT NULL,
    discount             NUMBER(10,2) DEFAULT 0.00 NOT NULL,
    released_at          TIMESTAMP,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT voucher_redemptions_pk PRIMARY KEY (id),
    CONSTRAINT voucher_redemptions_ref_uk UNIQUE (checkout_ref)
);

CREATE TABLE vouchers (
    id                   NUMBER(10) NOT NULL,
    code                 VARCHAR2(30 CHAR) NOT NULL,
    description          VARCHAR2(160 CHAR) NOT NULL,
    type                 VARCHAR2(8 CHAR) NOT NULL,
    value                NUMBER(10,2) NOT NULL,
    min_spend            NUMBER(12,2) DEFAULT 0.00 NOT NULL,
    max_discount         NUMBER(10,2),
    usage_limit          NUMBER(10),
    per_user_limit       NUMBER(10),
    expires_at           TIMESTAMP,
    is_active            NUMBER(1) DEFAULT 1 NOT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT vouchers_pk PRIMARY KEY (id),
    CONSTRAINT vouchers_code_uk UNIQUE (code),
    CONSTRAINT vouchers_type_ck CHECK (type IN ('percent', 'fixed', 'shipping'))
);

CREATE TABLE wishlists (
    id                   NUMBER(10) NOT NULL,
    user_id              NUMBER(10) NOT NULL,
    product_id           NUMBER(10) NOT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT wishlists_pk PRIMARY KEY (id),
    CONSTRAINT uq_wishlist_user_product UNIQUE (user_id, product_id)
);

-- ---------------- Column comments ----------------

-- ---------------- Relationships (foreign keys) ----------------
ALTER TABLE cart_items ADD CONSTRAINT products_added_to_cart_items FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE;
ALTER TABLE cart_items ADD CONSTRAINT users_have_cart_items FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE email_logs ADD CONSTRAINT orders_have_email_logs FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL;
ALTER TABLE email_logs ADD CONSTRAINT users_receive_email_logs FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;
ALTER TABLE order_items ADD CONSTRAINT orders_contain_order_items FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE;
ALTER TABLE order_items ADD CONSTRAINT products_appear_in_order_items FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL;
ALTER TABLE orders ADD CONSTRAINT buyers_place_orders FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE orders ADD CONSTRAINT sellers_fulfill_orders FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE password_resets ADD CONSTRAINT users_request_password_resets FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE payments ADD CONSTRAINT buyers_make_payments FOREIGN KEY (buyer_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE products ADD CONSTRAINT categories_group_products FOREIGN KEY (category_id) REFERENCES categories (id);
ALTER TABLE products ADD CONSTRAINT sellers_list_products FOREIGN KEY (seller_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE reviews ADD CONSTRAINT orders_have_reviews FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL;
ALTER TABLE reviews ADD CONSTRAINT products_receive_reviews FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE;
ALTER TABLE reviews ADD CONSTRAINT users_write_reviews FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;
ALTER TABLE shipment_events ADD CONSTRAINT orders_have_shipment_events FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE;
ALTER TABLE voucher_redemptions ADD CONSTRAINT users_redeem_vouchers FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;
ALTER TABLE voucher_redemptions ADD CONSTRAINT vouchers_have_redemptions FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE CASCADE;
ALTER TABLE wishlists ADD CONSTRAINT products_saved_in_wishlists FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE;
ALTER TABLE wishlists ADD CONSTRAINT users_have_wishlists FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE;

-- ---------------- Indexes ----------------
CREATE INDEX ix_cart_product ON cart_items (product_id);
CREATE INDEX ix_email_logs_user ON email_logs (user_id);
CREATE INDEX ix_email_logs_order ON email_logs (order_id);
CREATE INDEX ix_items_order ON order_items (order_id);
CREATE INDEX ix_items_product ON order_items (product_id);
CREATE INDEX ix_orders_buyer ON orders (buyer_id, created_at);
CREATE INDEX ix_orders_seller ON orders (seller_id, status);
CREATE INDEX ix_orders_checkout ON orders (checkout_ref);
CREATE INDEX ix_password_resets_user ON password_resets (user_id, created_at);
CREATE INDEX ix_payments_checkout ON payments (checkout_ref, status);
CREATE INDEX ix_payments_buyer ON payments (buyer_id);
CREATE INDEX ix_products_category ON products (category_id);
CREATE INDEX ix_products_status_price ON products (status, price);
CREATE INDEX ix_reviews_product ON reviews (product_id);
CREATE INDEX ix_reviews_order ON reviews (order_id);
CREATE INDEX ix_events_order ON shipment_events (order_id, created_at);
CREATE INDEX ix_users_role ON users (role);
CREATE INDEX ix_redemptions_voucher ON voucher_redemptions (voucher_id, released_at);
CREATE INDEX ix_redemptions_user ON voucher_redemptions (user_id, voucher_id, released_at);
CREATE INDEX ix_wishlist_product ON wishlists (product_id);
