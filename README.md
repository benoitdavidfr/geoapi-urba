# geoapi-urba

Proposition de définition pour l'API d'accès aux données
du [géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/).    

L'objectif de ce projet est de faciliter la concertation pour la définition de cette API.
L'API finale devrait être mise en oeuvre par l'IGN, opérateur du géoportail de l'urbanisme.

L'API est définie sur Swagger:
  - la [version 0.1.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.1.0) est un premier jet ;
    elle ne concerne que l'accès aux documents d'urbanisme ;
    aucune mise en oeuvre n'est proposée ;
    l'objectif était principalement de découvrir l'utilisation de Swagger ;
  - la [version 0.2.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.2.0) est proposée avec une première mise en oeuvre sur environ 1300 documents d'urbanisme ; les pièces écrites sont aussi exposées.

Ce dépôt contient les scripts de mise en oeuvre de l'API.    
L'initialisation est effectuée en moissonnant le géoportail de l'urbanisme au travers de son flux Atom pour récupérer un certain nombre de documents en se limitant à 100 Go de stockage des zip (la taille du disque).
L'analyse des données moissonnées génère une base MongoDB consultée lors de l'activation de l'API.
Les pièces écrites sont conservées dans les zips.

Le répertoire racine contient le script api2.php utilisé pour l'API.

Le répertoire build contient les scripts utilisés pour initialiser la base MongoDB.

