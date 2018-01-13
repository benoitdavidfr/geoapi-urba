# geoapi-urba

Proposition de définition pour l'API d'accès aux données
du [géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/).    

L'API est définie sur Swagger:
  - la [version 0.1.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.1.0) est un premier jet ;
    elle ne permet d'accéder qu'aux documents d'urbanisme ;
    aucune mise en oeuvre n'est proposée
  - la [version 0.2.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.2.0) est proposée avec une première mise en oeuvre sur environ 1300 documents d'urbanisme ; les pièces écrites sont aussi exposées.

Ce dépôt contient les scripts produits pour la mise en oeuvre de l'API.    
Pour initialiser cette mise en oeuvre, le géoportail de l'urbanisme a été moissonné pour récupérer un certain nombre de documents en se limitant à 100 Go de stockage des zip (la taille du disque).
Les données moissonnées ont été analysées et le résultat stocké dans une base MongoDB.
Lors de l'activation de l'API, les informations sont extraites de cette base à l'exception des pièces écrites qui sont
estraites des zips.

Le répertoire racine contient le script utilisé pour l'API (api2.php).

Le répertoire build contient les scripts utilisés pour initialiser la base MongoDB.

