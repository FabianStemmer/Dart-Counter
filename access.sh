sudo chown -R stemmer:www-data /var/www/html/dart-counter
sudo usermod -aG www-data stemmer
sudo find /var/www/html/dart-counter -type d -exec chmod 775 {} \;
sudo find /var/www/html/dart-counter -type f -exec chmod 664 {} \;
sudo find /var/www/html/dart-counter -type d -exec chmod g+s {} \;
sudo chmod -R 775 /var/www/html/dart-counter/storage /var/www/html/dart-counter/bootstrap/cache