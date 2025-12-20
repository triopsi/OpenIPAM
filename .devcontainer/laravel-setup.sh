#!/bin/bash

# Site configuration options
DB_HOST="db"
DB_NAME="laravel"
DB_USER="laravel_user"
DB_PASS="laravel_pass"
DB_PORT="3306"

# Navigate to the Laravel project directory
cd /var/www/html || exit

# Composer install all the project dependencies
read -p "Do you want to start the installation? (y/n): " choice
if [[ "$choice" == "y" ]]; then
    # Install PHP dependencies and Node.js packages
    composer install

    # Install front-end dependencies and build assets
    npm install
    npm run build
    # npm run build:modern
    
    # Set up the environment file
    # cp .env.example .env
    # sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=mariadb/" .env
    # sed -i "s/DB_HOST=.*/DB_HOST=${DB_HOST}/" .env
    # sed -i "s/DB_PORT=.*/DB_PORT=${DB_PORT}/" .env
    # sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
    # sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
    # sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env

    # Generate application key and run migrations
    php artisan key:generate
    php artisan config:cache
    php artisan migrate
    # php artisan db:seed --class=CustomPropertiesSeeder
    php artisan storage:link
    chown -R www-data:www-data /var/www/html/
    chmod -R 775 /var/www/html/storage
    chmod -R 775 /var/www/html/bootstrap/cache
    echo "Laravel installation and setup completed successfully."
else
    echo "Installation aborted."
fi