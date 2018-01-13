# geoapi-urba

Proposition de définition pour l'API d'accès aux données
du [géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/).    

L'objectif de ce projet est de faciliter la concertation sur la définition de cette API.
L'API finale devrait être mise en oeuvre par l'IGN, opérateur du géoportail de l'urbanisme.

L'API est définie sur Swagger:
  - la [version 0.1.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.1.0) est un premier jet ;
    elle ne permet d'accéder qu'aux documents d'urbanisme ;
    aucune mise en oeuvre n'est proposée ;
  - la [version 0.2.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.2.0) est proposée avec une première mise en oeuvre sur environ 1300 documents d'urbanisme ; les pièces écrites sont aussi exposées.

Ce dépôt contient les scripts produits pour la mise en oeuvre de l'API.    
Pour initialiser cette mise en oeuvre, le géoportail de l'urbanisme a été moissonné pour récupérer un certain nombre de documents en se limitant à 100 Go de stockage des zip (la taille du disque).
Les données moissonnées ont été analysées et le résultat stocké dans une base MongoDB
consultée lors de l'activation de l'API.
Les informations sont extraites de cette base à l'exception des pièces écrites qui sont conservées dans les zips.

Le répertoire racine contient le script api2.php utilisé pour l'API.

Le répertoire build contient les scripts utilisés pour initialiser la base MongoDB.

