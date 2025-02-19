# Procédure d'installation de Deming

## Configuration recommandée

- OS : Ubuntu 22.04 LTS
- RAM : 2G
- Disque : 120G
- VCPU 2

## Installation

Mettre à jour la distribution linux

    sudo apt update && sudo apt upgrade

Installer Apache, git, php et composer

    sudo apt-get install git composer apache2 libapache2-mod-php php php-cli php-opcache php-mysql php-zip php-gd php-mbstring php-curl php-xml -y

Créer le répertoire du projet

    cd /var/www
    sudo mkdir deming
    sudo chown $USER:$GROUP deming

Cloner le projet depuis Github

    git clone https://www.github.com/dbarzin/deming

Installer les packages avec composer :

    cd deming
    mkdir -p storage/framework/views
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p bootstrap/cache
    composer install

Publier tous les actifs publiables à partir des packages des fournisseurs

    php artisan vendor:publish --all

## MySQL

Installer MySQL

    sudo apt install mysql-server

Vérifier que vous utilisez MySQL et pas MariaDB (Deming ne fonctionne pas avec MariaDB).

    sudo mysql --version

Lancer MySQL avec les droits root
php artisan db:seed --class=AttributeSeeder
php artisan db:seed --class=DomainSeeder
php artisan db:seed --class=MeasureSeeder

    sudo mysql

Créer la base de données _deming_ et l'utilisateur _deming_user_

    CREATE DATABASE deming CHARACTER SET utf8 COLLATE utf8_general_ci;
	CREATE USER 'deming_user'@'localhost' IDENTIFIED BY 'demPasssword-123';
    GRANT ALL ON deming.* TO deming_user@localhost;
    GRANT PROCESS ON *.* TO 'deming_user'@'localhost';

    FLUSH PRIVILEGES;
    EXIT;

## Configuration

Créer un fichier .env dans le répertoire racine du projet :

    cd /var/www/deming
    cp .env.example .env

Mettre les paramètre de connexion à la base de données :

    vi .env

    ## .env file
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=deming
    DB_USERNAME=deming_user
    DB_PASSWORD=demPasssword-123

## Créer la base de données

Exécuter les migrations

    php artisan migrate --seed

Remarque: la graine est importante (--seed), car elle créera le premier utilisateur administrateur pour vous.

Générer la clé de l'application

    php artisan key:generate

Créer le lien de stockage

	php artisan storage:link

## Peupler la base de données

Il y a deux aptions pour peupler la base de données : avec commandes SQL ou avec les seeders.

### Seeder

Exécter les commandes suivantes

    php artisan db:seed --class=AttributeSeeder
    php artisan db:seed --class=DomainSeeder
    php artisan db:seed --class=MeasureSeeder

Si le une des commandes renvoie une erreur, la base de données n'était pas vide, il faut alors recréer la base de données avec la commande:

    php artisan migrate:fresh --seed

### SQL

Pour importer la base de données avec les mesures de sécurité de la norme 27001:2022

    php artisan db:seed --class=AttributeSeeder
    php artisan db:seed --class=DomainSeeder
    php artisan db:seed --class=MeasureSeeder

Génrérer des données de test (optionnel)

    php artisan deming:generateTests

Démarrer l'application avec php

    php artisan serve

ou pour y accéder à l'application depuis un autre serveur

    php artisan serve --host 0.0.0.0 --port 8000

L'application est accessible à l'URL [http://127.0.0.1:8000]

    utilisateur : admin@admin.localhost
    mot de passe : admin

L'administrateur utilise la langue anglaise par défaut. Pour changer de langue, allez dans la page de profil de l'utilisateur
(en haut à droite de la page principale).

## Apache

Pour configurer Apache, modifiez les propriétés du répertoire Deming et accordez les autorisations appropriées au répertoire de stockage avec la commande suivante :

    sudo chown -R www-data:www-data /var/www/deming
    sudo chmod -R 775 /var/www/deming/storage

Ensuite, créez un nouveau fichier de configuration d'hôte virtuel Apache pour servir l'application :

    sudo vi /etc/apache2/sites-available/deming.conf

Ajouter les lignes suivantes :

    <VirtualHost *:80>
    ServerName deming.local
    ServerAdmin admin@example.com
    DocumentRoot /var/www/deming/public
    <Directory /var/www/deming>
    AllowOverride All
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>

Enregistrez et fermez le fichier lorsque vous avez terminé. Ensuite, activez l'hôte virtuel Apache et le module de réécriture avec les commandes suivantes :

    sudo a2enmod rewrite
    sudo a2dissite 000-default.conf
    sudo a2ensite deming.conf
    sudo a2dismod php8.1
    sudo a2enmod proxy_fcgi setenvif
    sudo a2enconf php8.1-fpm

Enfin, redémarrez le service Apache pour activer les modifications :

    sudo systemctl restart apache2

## PHP

Vous devez définir les valeurs de upload_max_filesize et post_max_size dans votre php.ini :

    ; Taille maximale autorisée pour les fichiers téléchargés.
    upload_max_filesize = 10M

    ; Doit être supérieur ou égal à upload_max_filesize
    post_max_size = 10M

Après avoir modifié le(s) fichier(s) php.ini, vous devez redémarrer le service php-fpm  pour utiliser la nouvelle configuration.

    sudo systemctl restart php-fpm

## Configuration du mail

Si vous souhaitez envoyer des mails de notification depuis Deming.

Installer postfix et mailx

    sudo apt install postfix mailutils

Configurer postfix

    sudo dpkg-reconfigure postfix

Envoyer un mail de test avec

    echo "Test mail body" | mailx -r "deming@yourdomain.local" -s "Subject Test" yourname@yourdomain.local

## Sheduler

Modifier le crontab

    sudo crontab -e

ajouter cette ligne dans le crontab

    * * * * * cd /var/www/deming && php artisan schedule:run >> /dev/null 2>&1

## Mise à jour

Pour mettre à jour Deming, il faut aller dans le répoertoire de Deming et récupérer les sources

    cd /var/www/deming
    git pull

Migrer la base de données

    php artisan migrate

Mettre à jour composer

    composer self-update

Mettre à jour les librairies

    composer update

Vider les caches

    php artisan optimize:clear

## Remise à zéro

Pour repartir d'une base de données vide avec la norme ISO 27001:2022.

Voici la commande pour recréer la DB :

    php artisan migrate:fresh --seed

Puis pour peupler la DB avec la 27001:2022

    php artisan db:seed --class=AttributeSeeder
    php artisan db:seed --class=DomainSeeder
    php artisan db:seed --class=MeasureSeeder
