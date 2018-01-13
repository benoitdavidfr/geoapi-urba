# geoapi-urba - répertoire build

Ce répertoire contient les scripts utilisés pour initialiser la base MongoDB utilisée par l'API d'accès aux données
du [géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/).    

Description succinctes des principaux scripts Php :
  - atom.php permet de consulter le flux Atom du GpU,
  - dwnld.php effectue le moissonnage du GpU et stocke les zips dans un répertoire zips,
  - build.php fabrique la collection des documents d'urbanisme dans MongoDB,
  - mkaut.php génère la collection des autorités dans MongoDB en croisant les documents d'urbanisme
    avec la base Admin-Express ; les seules autorités retenues sont celles dont le code existe dans Admin-Express.
