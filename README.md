# Document Scolarité ENT

Plateforme web de gestion et distribution de documents scolaires (relevés de notes, attestations de réussite) pour établissements d'enseignement supérieur.

## Fonctionnalités

- **Imports documentaires** : Upload PDF + fichiers .etu (relevés de notes, attestations)
- **Traitement PDF** : Découpage automatique par étudiant avec regex personnalisables
- **Tamponnage** : Ajout optionnel de tampons (images PNG) sur les documents
- **Distribution** : Transfert sécurisé vers espaces étudiants + emails de notification
- **Portail étudiant** : Accès personnalisé aux relevés et attestations
- **Historique** : Suivi complet des imports et transferts
- **Gestion des droits** : Administrateurs, gestionnaires, étudiants (via LDAP/CAS)

## Stack Technique

**Backend:**
- Symfony 6.4 (PHP 8.1+)
- Doctrine ORM
- MySQL/MariaDB
- LDAP (Symfony/ldap)
- CAS Auth (yraiso/casauth-bundle)

**Frontend:**
- Vue.js 3
- Bootstrap 5
- AG Grid Vue3
- Webpack Encore

**Outils:**
- qpdf (manipulation PDF)
- FPDI/FPDF (génération PDF)
- Ghostscript (compression images)

## Prérequis

### Système
- Linux (CentOS 7+, Ubuntu 18.04+, Debian 10+)
- PHP 8.1+ avec extensions: ctype, iconv, json, openssl, pdo, mbstring, xml, ldap, mysqlnd
- MySQL/MariaDB
- Apache/nginx avec mod_rewrite

### Dépendances
```bash
# CentOS/RHEL
yum install php81 php81-ldap php81-mysqlnd ghostscript qpdf git

# Ubuntu/Debian
apt-get install php8.1 php8.1-ldap php8.1-mysql ghostscript qpdf git
```

### Authentification
- Serveur LDAP (récupération infos utilisateur et rôles)
- Serveur CAS (SSO centralisé)

## Installation

### 1. Récupération du code
```bash
cd /var/www/html
git clone https://github.com/UCA-Squad/Document-Scolarite-ENT.git
cd Document-Scolarite-ENT
```

### 2. Dépendances
```bash
# PHP
composer install

# JavaScript
npm install
```

### 3. Configuration
```bash
cp .env .env.local
# Éditer .env.local (voir section Configuration)
```

### 4. Base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Assets
```bash
npm run build  # production
# ou
npm run dev    # développement
```

### 6. Permissions
```bash
chown -R apache:apache /var/www/html/Document-Scolarite-ENT
chmod -R 775 /var/www/html/Document-Scolarite-ENT/var
chmod -R 775 /var/www/html/Document-Scolarite-ENT/output
```

### 7. Serveur web

**Apache** (`/etc/apache2/sites-available/scolarite.conf`):
```apache
<VirtualHost *:80>
    ServerName scolarite.etablissement.fr
    DocumentRoot /var/www/html/Document-Scolarite-ENT/public
    
    <Directory /var/www/html/Document-Scolarite-ENT/public>
        AllowOverride All
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/scolarite_error.log
    CustomLog ${APACHE_LOG_DIR}/scolarite_access.log combined
</VirtualHost>
```

Activer:
```bash
a2ensite scolarite.conf
a2enmod rewrite
systemctl restart apache2
```

**nginx** (`/etc/nginx/sites-available/scolarite.conf`):
```nginx
server {
    listen 80;
    server_name scolarite.etablissement.fr;
    root /var/www/html/Document-Scolarite-ENT/public;
    
    location / {
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

Activer:
```bash
ln -s /etc/nginx/sites-available/scolarite.conf /etc/nginx/sites-enabled/
systemctl restart nginx
```

## Configuration

Éditer `.env.local` :

### Base de données
```env
DATABASE_URL="mysql://user:password@localhost:3306/scolarite_db"
```

### CAS
```env
CAS_LOGIN_URL="https://cas.etablissement.fr/cas/login"
CAS_VALIDATION_URL="https://cas.etablissement.fr/cas/serviceValidate"
CAS_LOGOUT_URL="https://cas.etablissement.fr/cas/logout"
```

### LDAP
```env
LDAP_URL="ldaps://ldap.etablissement.fr:636"
LDAP_BIND_DN="cn=export,ou=local,dc=etablissement,dc=fr"
LDAP_BIND_PASSWORD="password"
LDAP_BASE_DN="dc=etablissement,dc=fr"
LDAP_BL_GROUP_DN="cn=blacklist,ou=groups,dc=etablissement,dc=fr"

LDAP_CODE="CLFDcodeEtu"
LDAP_AFFILIATION="eduPersonAffiliation"
LDAP_AFFILIATION_STUDENT="student"

ADMIN_UID='["admin1", "admin2"]'
LDAP_STATUS="CLFDstatus"
LDAP_AFFECTATION="supannEntiteAffectationPrincipale"
```

### Stockage
```env
OUTPUT_DIR_RN="/var/www/html/Document-Scolarite-ENT/output/releves/"
OUTPUT_TMP_RN="/var/www/html/Document-Scolarite-ENT/output/tmp_rn/"
OUTPUT_ETU_RN="/var/www/html/Document-Scolarite-ENT/output/etu_rn/"
OUTPUT_DIR_ATTEST="/var/www/html/Document-Scolarite-ENT/output/attestations/"
OUTPUT_TMP_ATTEST="/var/www/html/Document-Scolarite-ENT/output/tmp_attest/"
OUTPUT_ETU_ATTEST="/var/www/html/Document-Scolarite-ENT/output/etu_attest/"
OUTPUT_TMP_PDF="/var/www/html/Document-Scolarite-ENT/output/tmp_pdf/"
OUTPUT_TMP_TAMPON="/var/www/html/Document-Scolarite-ENT/public/tampons/"
```

### Email
```env
MAILER_DSN="smtp://smtp.etablissement.fr:587?encryption=tls&auth_mode=login&username=user&password=pass"
MAILER_FROM="noreply@etablissement.fr"
APP_ENV="prod"
```

### Autres
```env
IS_MAINTENANCE=false
```

## Rôles et Authentification

L'authentification fonctionne de manière **hybride** :
- **Administrateurs, Étudiants, Blacklistés** : Créés dynamiquement à la connexion (pas en BDD)
- **Gestionnaires** : Les SEULS utilisateurs persistés en base de données

| Rôle | Condition | Stockage |
|------|-----------|----------|
| **ADMIN** | UID dans `ADMIN_UID` (.env.local) | Dynamique |
| **SCOLA** (Gestionnaire) | `CLFDstatus=9` en LDAP + présent en BDD | **BDD** |
| **ETUDIANT** | `LDAP_AFFILIATION` contient `LDAP_AFFILIATION_STUDENT` | Dynamique |
| **ANONYMOUS** | Membre de `LDAP_BL_GROUP_DN` (LDAP) | Dynamique |

### Création des gestionnaires

**Seuls les administrateurs (`ADMIN_UID`) peuvent créer des gestionnaires.**

Deux conditions obligatoires pour un utilisateur :
1. **En LDAP** : L'utilisateur doit avoir `CLFDstatus=9`
2. **Dans l'app** : Créé via l'interface d'administration (`/admin`)

**Interface d'administration** :
- Accès : Administrateurs uniquement
- Rechercher un utilisateur par UID ou nom
- Ajouter l'utilisateur (crée automatiquement `ROLE_SCOLA` en BDD)
- Gérer les groupes d'utilisateurs

### Groupes d'utilisateurs

Les gestionnaires peuvent être organisés en **groupes** avec contrôle d'accès :
- **Créer/modifier groupes** : Via l'interface admin (`/admin` > onglet Groupes)
- **Responsables de groupe** : Voient et gèrent les imports de tous les membres du groupe + leurs propres imports
- **Utilisateurs du groupe** : Membres sans responsabilité (accès que à leurs propres imports)
- **Accès restreint** : Un gestionnaire ne voit que les documents qu'il a le droit de consulter selon son statut dans les groupes
  - Admin : voit tous les documents
  - Responsable : voit les documents du groupe et les siens
  - Utilisateur simple : voit uniquement ses propres documents



## Utilisation

### Gestionnaires
1. Authentification CAS/LDAP
2. Accès `/scola` automatique
3. Import : PDF + fichier .etu
4. Tamponnage (optionnel)
5. Sélection et transfert aux étudiants

### Étudiants
1. Authentification CAS/LDAP
2. Accès automatique à ses documents
3. Téléchargement PDF

### Administrateurs
- Tous les droits
- Monitoring et historique

## Structure du Projet

```
src/
├── Controller/        # API et contrôleurs web
├── Entity/           # Entités Doctrine
├── Logic/            # Services métier
├── Parser/           # Parseurs PDF et .etu
├── Security/         # Authentification
└── Repository/       # Requêtes BDD

assets/
├── pages/           # Pages Vue.js
├── WebService.js    # Client API
└── styles/          # CSS

config/             # Configuration Symfony
templates/          # Templates Twig
migrations/         # Migrations Doctrine
output/             # Stockage fichiers
public/             # Fichiers publics
```

