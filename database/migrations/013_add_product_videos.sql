-- Optional product video (YouTube link or an uploaded .mp4/.webm), shown before the photos.
ALTER TABLE products ADD COLUMN video_url VARCHAR(500) NULL AFTER images;

-- Official brand videos for a few demo products (only where no video is set yet).
UPDATE products SET video_url = 'https://www.youtube.com/watch?v=72cAe_2LAFQ' WHERE name = 'iPhone 13 Pro' AND video_url IS NULL;
UPDATE products SET video_url = 'https://www.youtube.com/watch?v=FXI_-OesT3A' WHERE name = 'Apple AirPods Max Silver' AND video_url IS NULL;
UPDATE products SET video_url = 'https://www.youtube.com/watch?v=9tobL8U7dQo' WHERE name = 'Apple MacBook Pro 14 Inch Space Grey' AND video_url IS NULL;
UPDATE products SET video_url = 'https://www.youtube.com/watch?v=Qn8eGdVO6fI' WHERE name = 'Samsung Galaxy Tab S8 Plus Grey' AND video_url IS NULL;
