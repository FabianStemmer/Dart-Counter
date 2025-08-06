#!/bin/bash

# Verzeichnis des Laravel-Projekts (passe ggf. an)
PROJECT_DIR="/var/www/html/dart-counter"

echo "Setze Berechtigungen für Laravel-Projekt in $PROJECT_DIR ..."

# Eigentümer für storage und bootstrap/cache auf www-data:www-data setzen
sudo chown -R www-data:www-data "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

# Gruppe für storage und bootstrap/cache auf www-data setzen und Nutzer stemmer der Gruppe www-data hinzufügen
sudo usermod -aG www-data stemmer
sudo chown -R stemmer:www-data "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

# Berechtigungen auf 775 setzen
sudo chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

echo "Fertig. Überprüfe die Gruppenmitgliedschaft von 'stemmer' mit: id stemmer"
echo "Starte ggf. die WSL-Instanz neu, damit Gruppenänderungen aktiv werden."
