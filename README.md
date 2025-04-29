Dans ce projet, nous avons mis en place trois méthodes de compression d'image en réduisant le nombre de couleurs dans l'image.
- Méthode naïve en prenant les couleurs les plus présentes
- Méthode k-mean dans l'espace colorimétrique RGB
- Méthode k-mean dans l'espace colorimétrique LAB

Nous avons aussi comparé nos résultats avec ceux de la fonction quantizeImage d'imagick

# 1. Fonction nécessaire à l'implémentation

Nous avons implémenté plusieurs fonctions utiles pour notre implémentation et le calcul des erreurs afin de comparer les résultats.

## 1.1. Distance euclidienne
La fonction `distanceEuclidienne` prend deux couleurs en paramètre et renvoie la distance euclidienne entre elles. La distance est calculée dans l’espace colorimétrique dans lequel les couleurs sont fournies à la fonction. 

Nous n’avons pas inclus la racine carrée, car son calcul prend plus de temps, et cela ne change pas les comparaisons de distances.

## 1.2. Calcul des erreurs

Le calcul des erreurs est effectué avec la fonction `erreurImage`, qui prend en paramètre l’image source et l’image recolorée, puis renvoie l’erreur.

L’erreur est définie comme la somme des distances euclidiennes entre chaque pixel des deux images, divisée par le nombre total de pixels. Elle est calculée dans l'espace RGB.


## 1.3. Conversion RGB a LAB et LAB a RGB

Nous avons implémenté dans `conversion.php` les fonctions permettant de passer de l'espace RGB a LAB et inversement.

La conversion RGB vers LAB a lieu en comme suit:
1. Normalisation: On divise les composantes RGB par 255
2. Correction gamma: On applique la transformation inverse du gamma pour linéariser les valeurs RGB avant leur conversion en XYZ.
3. Conversion en XYZ: On applique la matrice de transformation standard RGB vers XYZ
4. Conversion de XYZ vers LAB: Pour obtenir la couleur LAB, on applique la transformation de l'espace XYZ vers LAB en utilisant les valeurs de référence de l'illuminant standard D65. 

La conversion LAB vers RGB a lieu en comme suit:
1. Récupération des valeurs XYZ: On applique la transformation inverse de LAB vers XYZ en utilisant les formules.
2. Passage de XYZ à RGB: On applique la matrice inverse pour obtenir les valeurs RGB linéaires.
3. Correction gamma: On applique la correction gamma standard pour revenir aux valeurs sRGB.
4. mise à l’échelle: On ramène les valeurs RGB à l’échelle [0, 255].

# 2. Implementation

## 2.1. Méthode naive
La méthode naïve consiste à prendre les couleurs les plus présentes dans l'image afin de former la palette qui nous permet de recolorer l'image. Chaque pixel de l'image recolorée est ensuite mis à la couleur la plus proche dans la palette.

## 2.2. K-mean

Notre fonction `kMean` prend quatre paramètres :
- L'image source : l'image sur laquelle l'algorithme sera appliqué.
- Le nombre de couleurs dans la palette (k) : correspond au nombre de clusters recherchés.
- L'espace colorimétrique : choix entre RGB et LAB.
- L'epsilon : critère d'arrêt basé sur la convergence de l'algorithme.

Déroulement de l'algorithme :

1. Chargement des couleurs des pixels : La fonction `chargerPixel` extrait les couleurs de l’image et les stocke dans un tableau 1D. La structure 2D de l’image est ignorée, car seules les couleurs sont nécessaires. Cela permet d’optimiser la rapidité et de réduire l'utilisation mémoire. Les couleurs sont traitées en RGB ou converties en LAB selon le paramètre choisi.
2. Initialisation des centroïdes : On sélectionne aléatoirement k couleurs de l’image pour servir de centroïdes initiaux.
3. Assignation des couleurs aux centroïdes : À chaque itération, chaque couleur est affectée au centroïde le plus proche dans l’espace colorimétrique sélectionné.
4. Mise à jour des centroïdes : Chaque centroïde est recalculé en prenant la moyenne des couleurs qui lui ont été assignées.
5. Boucle et critère d’arrêt : L’algorithme répète les étapes 2 à 4 jusqu’à convergence ou après 100 itérations maximum. La convergence est atteinte lorsque la somme des distances entre les centroïdes actuels et ceux de l'itération précédente est inférieure à epsilon.


Une fois la boucle terminée, les centroïdes sont convertis en RGB s'ils étaient initialement en LAB. Ils sont ensuite retournés et constituent la palette de couleurs de la nouvelle image.
