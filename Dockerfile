# =============================================================================
# Stage 1: Composer dependencies (ติดตั้งแพ็คเกจ PHP)
# =============================================================================
FROM composer:2.9.5 AS composer

# ตั้งค่าโฟลเดอร์ทำงาน
WORKDIR /app

# คัดลอกไฟล์ composer
COPY composer.json composer.lock ./

# เคลียร์แคชของ Composer และติดตั้ง dependencies โดยไม่รวม dev packages
# หมายเหตุ: ยังไม่ optimize autoloader ตอนนี้ เพราะยังไม่มี source code ของ app
# จะรัน dump-autoload --optimize อีกทีใน stage สุดท้ายหลัง COPY source code
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --no-autoloader

# =============================================================================
# Stage 2: Node.js (สร้าง Frontend Assets)
# =============================================================================
FROM node:20-alpine AS node

# ตั้งค่าโฟลเดอร์ทำงาน
WORKDIR /app

# คัดลอกไฟล์ package
COPY package.json package-lock.json ./

# ติดตั้ง npm dependencies
RUN npm ci

# คัดลอกโค้ดสำหรับ vite
COPY vite.config.js ./
COPY resources ./resources

# Build assets สำหรับใช้งาน
RUN npm run build

# =============================================================================
# Stage 3: Production image
# =============================================================================
FROM php:8.4-apache AS production

# เปิดใช้งาน mod_rewrite ของ Apache
RUN a2enmod rewrite

# ตั้งค่า Apache document root ไปที่โฟลเดอร์ public ของ Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ติดตั้ง dependencies ที่จำเป็นของระบบ
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ตั้งค่า OPcache สำหรับ Production
RUN echo "opcache.enable=1" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.memory_consumption=128" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.interned_strings_buffer=8" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.max_accelerated_files=10000" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.validate_timestamps=0" >> "$PHP_INI_DIR/conf.d/opcache.ini" \
    && echo "opcache.save_comments=1" >> "$PHP_INI_DIR/conf.d/opcache.ini"

# ตั้งค่าการใช้งาน PHP สำหรับ Production
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# ตั้งค่าโฟลเดอร์ทำงาน
WORKDIR /var/www/html

# คัดลอกโค้ด Laravel ภายใน container
COPY . .

# นำ dependencies จาก Stage 1 (Composer) มาใช้งาน
COPY --from=composer /app/vendor ./vendor

# นำไฟล์ที่ Build แล้วจาก Stage 2 (Node) มาใช้งาน
COPY --from=node /app/public/build ./public/build

# สร้าง optimized autoloader หลังจากมี source code + vendor ครบแล้ว
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev \
    && rm /usr/bin/composer

# ตั้งค่า permission ให้ Laravel ทำงานได้
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

# คัดลอก script สำหรับเช็ตอัพระบบก่อนเริ่มทำงาน
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# เปิดพอร์ต 80 เพื่อให้ container รับ HTTP ได้
EXPOSE 80

# นำ script มาทำงานเป็น Entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
