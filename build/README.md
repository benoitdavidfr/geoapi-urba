# geoapi-urba - répertoire build

Ce répertoire contient les scripts utilisés pour initialiser la base MongoDB utilisée pour l'API d'accès aux données
du [géoportail de l'urbanisme](https://www.geoportail-urbanisme.gouv.fr/).    

Description succinctes du contenu :
  - le script Php atom.php permet de consulter le flux Atom du GpU,
  - le script Php dwnld.php effectue le moissonnage du GpU et stocke les zips dans un répertoire zips,
  - le script Php build.php fabrique la collection des documents d'urbanisme dans MongoDB,
  - le script Php mkaut.php génère la collection des autorités dans MongoDB en croisant les documents d'urbanisme
    avec la base Admin-Express ; les seules autorités retenues sont celles dont le code existe dans Admin-Express.
