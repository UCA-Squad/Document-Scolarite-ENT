- Prérequis : 
    --
    
    - Serveur Web :
        - `yum install httpd`
    - Serveur Mysql :
        - `yum install mariadb mariadb-server`
    - PHP et extensions :
        - `yum install php-fpm php-cli php-json php-pdo php-mbstring php-xml php-ldap php-mysqlnd`
    - Installer le logiciel Ghostscript : 
        - `yum install ghostscript`
    - Installer Composer : 
        - `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"`
        - `php composer-setup.php --install-dir=/bin --filename=composer`
    - Installer Git :
        - `yum install git`

- Installation et Configuration serveur
    --

1. Récupérer le code de l'application et le placer dans le dossier /var/www/html : `git clone https://github.com/UCA-Squad/Document-Scolarite-ENT.git`
2. Dans le dossier de l'application, installation avec le composer : `composer install` ou `composer update` (en cas d'erreur mémoire : augmenter la valeur de la variable memory_limit dans le fichier /etc/php.ini)
3. Il faut que le serveur web puisse écrire dans les dossiers var/cache et var/log de l'application. Pour cela, les manipulations diffèrent en fonction de votre système :
 
* Avec SELinux :
    * `semanage fcontext -a -t httpd_sys_content_rw_t '/var/www/html/Document-Scolarite-ENT/var/cache(/.*)?'`
    * `restorecon -v -R /var/www/html/Document-Scolarite-ENT/var/cache/`


* Avec ACL (Notez que tous les serveurs web n'utilisent pas l'utilisateur www-data. Vous devez vérifier quel utilisateur votre serveur web utilise et le remplacer dans la commande ci-dessous si besoin) :
   * `sudo setfacl -R -m u:www-data:rwx -m u:whoami:rwx var/cache`
   * `sudo setfacl -dR -m u:www-data:rwx -m u:whoami:rwx var/cache`

Il faut effectuer ces commandes pour les dossiers var/cache et var/log.

4. Creér une copie du fichier .env et le nommer .env.local. C'est ce fichier qui sera utilisé par l'application pour définir plusieurs paramètres.

5. Dans le fichier .env.local, modifiez la variable `DATABASE_URL` en y indiquant le pseudo et mot de passe de votre utilisateur mysql ainsi que le nom de votre base.
Ensuite, à la racine de l'application, utilisez la commande `php bin/console doctrine:database:create` pour créer la base de données.<br>
Puis `php bin/console doctrine:schema:update --force` afin de mettre à jour les tables.

6. Si vous rencontrez une erreur cURL en accedant à l'application via votre navigateur, utilisez cette commande : `setsebool -P httpd_can_network_connect 1`
---
Paramétrage PHP.ini:

Afin de s'assurer du bon fonctionnement de l'application, il faut revoir à la hausse certaines variables du fichier /etc/php.ini pour travailler avec des fichiers conséquents.
1. `post_max_size` et `upload_max_filesize` déterminent la taille maximum des fichiers a upload sur le serveur. Il est conseillé de les mettre à 100M voir plus en fonction de vos besoin.
2. Augmenter la variable `max_input_vars` à 3500 pour éviter les problèmes d'appels ajax
3. Si vous avez une erreur de mémoire limitée, il faut augmenter la variable `memory_limit` : 512M devrait être suffisant. 


- Connection CAS
    -- 
Pour personnaliser la connection au serveur CAS de votre établissement, éditez les URL dans le .env.local :

```
CAS_LOGIN_URL="https://etablissement.fr/cas/"
CAS_VALIDATION_URL="https://etablissement.fr/cas/serviceValidate"
CAS_LOGOUT_URL="https://etablissement.fr/cas/logout"
```

- Configuration LDAP
    --
L'application récupère via le LDAP de votre établissement le rôle d'un utilisateur ainsi que le numéro étudiant.<br>
Les paramètres LDAP sont modifiables dans le .env.local :

```
LDAP_URL=ldaps://ldap.etablissement.fr:636
LDAP_BIND_DN="cn=export,ou=local,dc=uca,dc=fr"
LDAP_BIND_PASSWORD="motdepasse"
LDAP_BASE_DN="dc=etablissement,dc=fr"
LDAP_ADMIN_GROUP_DN="cn=groupe-ldap-gestionnaire,ou=groups,dc=etablissement,dc=fr"
```
Afin d'obtenir le rôle de gestionnaire, les utilisateurs doivent appartenir à un groupe LDAP (Exemple : groupe-ldap-gestionnaire) défini dans la variable LDAP_ADMIN_GROUP_DN.

- Les rôles
    --
Il existe 3 type de rôles différents : 
* Administrateur : Défini dans la variable `ADMIN_UID` du .env.local - Accès a l'intégralité des fonctionnalités
* Gestionnaire : Doit faire parti du groupe LDAP gestionnaire mis en place ci-dessus - Accès aux imports et aux documents des étudiants
* Etudiant : Accès aux documents correspondants à son numéro étudiant

- Les dossiers de stockage
    --
L'application utilise différents dossiers afin de stocker les documents générés. Ils sont modifiables dans le .env.local : 
```
OUTPUT_DIR_RN=dossier_final_releves/            # Stockage final des relevés
OUTPUT_TMP_RN=dossier_tmp_releves/              # Stoackage temporaire des relevés (pendant la phase de sélection)
OUTPUT_ETU_RN=dossier_etu_releves/              # Stoackage temporaire du fichier .etu (pendant la phase de sélection)
OUTPUT_DIR_ATTEST=dossier_final_attestations/   # Stockage final des attestations
OUTPUT_TMP_ATTEST=dossier_tmp_attestations/     # Stoackage temporaire des attestations (pendant la phase de sélection)
OUTPUT_ETU_ATTEST=dossier_etu_attestations/     # Stoackage temporaire du fichier .etu (pendant la phase de sélection)
```
Votre serveur web doit être propriétaire de ces dossiers afin de pouvoir écrire dedans : `chown -R apache:apache dossier_final_releves`.

- Extraction des données du fichier .etu
    --
Afin de récupérer des informations sur les étudiants, l'application les extrait d'un fichier .etu.
Ce fichier est un .csv  sans en-tête. Chaque ligne de ce fichier correspond à un étudiant.
Par défaut, chaque ligne est convertie en [Student](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/master/src/Entity/Student.php) par le [StudentNormalizer](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/master/src/Normalizer/StudentNormalizer.php). Cette classe peut être modifiée dans le service.yaml : 
```
    # Normalizer use to transform one line of etu file into Student
    normalizer:
        class: App\Normalizer\StudentNormalizer
        tags: ['serializer.normalizer']
```
Pour fonctionner, le Normalizer doit étendre la classe ObjectNormalizer et implémenter les fonctions denormalize et supportsDenormalization.

- Parsing et Extraction
    --
L'application utilise le service [EtuParser](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/master/src/Parser/EtuParser.php) afin de parser le fichier .etu. Ce service renseigné dans le services.yaml peut être modifié : 
```
    # Parser use to extract info from file
    parser.etu:
        class: App\Parser\EtuParser # Has to implement IEtuParser
        arguments: ["@service_container"]
```
Ce service défini certaines fonctionnalités de l'application :
* *parseETU* : transforme un fichier .etu en une liste d'étudiants. Par défaut chaque ligne est envoyée au normalizer qui instancie un Student
* *getNbDoublons* : retourne le nombre d'étudiants en doublon dans le fichier .etu (cas avec pagination)
* *getReleveFileName* : retourne le nom qui sera utilisé pour nommer les relevés de notes
* *getAttestFileName* : retourne le nom qui sera utilisé pour nommer les attestations de réussite
* *findStudentByNum* : permet de retrouver un étudiant via son numéro dans le contenu d'une page pdf
* *findStudentByName* : permet de retrouver un étudiant via son nom, prénom et date de naissance dans le contenu d'une page pdf

Pour personnaliser ces fonctionnalités, vous pouvez créer votre propre service :
* En héritant de [EtuParser](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/master/src/Parser/EtuParser.php) afin de redéfinir une ou plusieurs fonctions
* En créant entièrement votre service en héritant de [IEtuParser](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/master/src/Parser/IEtuParser.php)

Les fichiers pdfs étants différents selon les composantes, les fonctions findStudentBy utilisent une liste de regex qui se trouvent dans le dossier src/Parser.
Vous pouvez ajouter des regexs en éditant les fichiers [NameRegexes.json](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/dev/src/Parser/NameRegexes.json) et [NumRegexes.json](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/dev/src/Parser/NumRegexes.json) : 
* Pour la recherche par numéro, la fonction teste chaque élément capturé comme numéro étudiant,
* Pour la recherche par nom/prénom/date, chaque regex est accompagnée de l'index des élements capturés correspondant au nom,prenom et date :

    - Si le nom et le prénom sont captués dans deux groupes différents :<br>
"regex": `/(.+) épouse [^ .]+ (.+)né\\(e\\) le ([0-9]{2}\\/[0-9]{2}\\/[0-9]{4})/`,<br>
"indexNom": **2**,     -> le 2nd group match le nom<br>
"indexPrenom": **1**,  -> le 1er group match le prénom<br>
"indexDate": 3      -> le 3èm group match la date<br>

    - Si le nom et le prénom sont capturés dans le même groupe, alors mettre le même index pour les deux : <br>
"regex": `/(.+) né\\(e\\) le ([0-9]{2}\\/[0-9]{2}\\/[0-9]{4})/`,<br>
"indexNom": **1**, -> le 1er groupe match le nom et le prenom<br>
"indexPrenom": **1**, -> le 1er groupe match le nom et le prenom<br>
"indexDate": 2<br>

    - Si la regex ne capture pas la date de naissance, alors mettre indexDate à -1;

- Maintenance
    --
Si vous désirez rendre inaccessible l'application à tous les utilisateurs, il est possible d'activer le mode maintenance. Dès lors, les utilisateurs seront redirigés vers la page [closed.html.twig](https://gitlab.dsi.uca.fr/dev/ent-doc-scola/-/blob/master/templates/closed.html.twig).
Cette fonctionnalité est activable en éditant la variable `IS_MAINTENANCE`à `TRUE` dans le .env.local.

- Les Routes
    -- 
L'application possède deux routes principales :
    - Celle de base redirige les utilisateurs avec le rôle étudiant sur l'écran étudiant et les gestionnaires sur la page de recherche
    - /scola est la route principale des gestionnaires
