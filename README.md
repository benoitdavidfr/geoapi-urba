# geoapi-urba

Proposition de définition pour l'API d'accès aux données
du [géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/).    

L'objectif de ce projet est de faciliter la concertation pour la définition de cette API.
L'API finale devrait être mise en oeuvre par l'IGN, opérateur du géoportail de l'urbanisme.

L'API est spécifiée sur Swagger, conformément aux préconisations de la DINSIC :
  - la [version 0.1.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.1.0) est un premier jet ;
    elle ne concerne que l'accès aux documents d'urbanisme ;
    aucune mise en oeuvre n'est proposée ;
    l'objectif était principalement d'expérimenter l'utilisation de Swagger ;
  - la [version 0.2.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.2.0) correspond
    à une première mise en oeuvre sur environ 1300 documents d'urbanisme y compris les pièces écrites associées.
  - la [version 0.3.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.3.0) propose l'accès aux
    servitudes d'utilité publiques (SUP).
  - la [version 0.4.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.4.0) redéfinit l'accès aux
    servitudes d'utilité publiques (SUP).
  - la [version 0.5.0](https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.5.0) ajoute à la description
    des pièces écrites et des actes leur taille.

Ce dépôt contient les scripts de mise en oeuvre de l'API.    
Les données exposées par l'API sont moissonnées dans le géoportail de l'urbanisme au travers de son flux Atom ;
dans un premier temps on se limite à 100 Go de stockage des zip (la taille du disque).
L'analyse des données moissonnées génère une base MongoDB qui est consultée lors de l'activation de l'API.
Les pièces écrites sont conservées dans les zips.

Une [version de démonstration de l'API est exposée sur l'URL](http://urba.geoapi.fr).

Exemples de requêtes illustrant l'API:
- [Liste des autorités en charge de documents d'urbanisme](http://urba.geoapi.fr/autorites)
- [Caractéristiques de la commune de Bénodet (29), y compris identifiant de son PLU](http://urba.geoapi.fr/autorites/29006)
- [Caractéristiques du PLU de la commune de Bénodet (29), y compris URL des pièces écrites](http://urba.geoapi.fr/docurba/29006_PLU_20170324)
- [Consultation d'une des pièces écrites du PLU de la commune de Bénodet (29)](http://urba.geoapi.fr/docurba/29006_PLU_20170324/Pieces_ecrites/2_PADD/29006_padd_20170324.pdf)
- [Liste des zones du PLU de la commune de Bénodet (29)](http://urba.geoapi.fr/docurba/29006_PLU_20170324/ZONE_URBA)
- [Liste des gestionnaires en charge des SUP](http://urba.geoapi.fr/gestionnaires)
- [Liste des catégories de SUP](http://urba.geoapi.fr/categoriesSup)
- [Codification des territoires pour lesquels sont définis des jeux de données de SUP (JD SUP)](http://urba.geoapi.fr/territoires)
- [Liste des JD SUP pour la catégorie PM1](http://urba.geoapi.fr/jdsup?codeSup=PM1)
- [Caractéristiques du JD SUP PM1 sur le département 40, y compris URL des actes](http://urba.geoapi.fr/jdsup/130010481_PM1_040_20171215)
- [Consultation d'un des actes du JD SUP PM1 sur le département 40](http://urba.geoapi.fr/jdsup/130010481_PM1_040_20171215/Actes/PM1_PPRI_Aire_sur_Adour_20000629_act.pdf)
- [Liste des zones de SUP du JD SUP PM1 sur le département 40](http://urba.geoapi.fr/jdsup/130010481_PM1_040_20171215/ASSIETTE_SUP_S)



Le répertoire racine contient le script api2.php utilisé pour l'API.

Le répertoire build contient les scripts utilisés pour initialiser la base MongoDB.

Documentation complémentaire:
  - [Flux Atom du géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/atom/download-feed/)
  - [Standards CNIG de dématérialisation des documents d'urbanisme](http://cnig.gouv.fr/?page_id=2732)
  - [Guide méthodologique de numérisation des SUP](http://www.geoinformations.developpement-durable.gouv.fr/servitudes-d-utilite-publiques-sup-r978.html)
