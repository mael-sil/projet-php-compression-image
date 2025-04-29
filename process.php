<?php
/**
 * Processus de quantification des couleurs
 * Traite l'image téléchargée par l'utilisateur.
 */

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit;
}

// Vérifier que l'extension GD est disponible
if (!extension_loaded('gd')) {
    die("L'extension GD est requise pour ce script");
}

// Créer les répertoires nécessaires s'ils n'existent pas
$uploadsDir = 'uploads';
$outputDir = 'output';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}


// Vérifier si un fichier a été téléchargé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    die("Erreur lors du téléchargement de l'image");
}

// Vérifier le type de fichier
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['image']['type'], $allowedTypes)) {
    die("Type de fichier non autorisé. Seuls les formats JPEG, PNG et GIF sont acceptés.");
}

// Déplacer le fichier téléchargéi
$sourceFile = $uploadsDir . '/source.jpg';
if (!move_uploaded_file($_FILES['image']['tmp_name'], $sourceFile)) {
    // Check the error type and print a message
    switch ($_FILES['image']['error']) {
        case UPLOAD_ERR_OK:
            echo ("No error. File was uploaded successfully.");
            break;
        case UPLOAD_ERR_INI_SIZE:
            echo ("Error: The uploaded file exceeds the upload_max_filesize directive in php.ini.");
            break;
        case UPLOAD_ERR_FORM_SIZE:
            echo ("Error: The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.");
            break;
        case UPLOAD_ERR_PARTIAL:
            echo ("Error: The uploaded file was only partially uploaded.");
            break;
        case UPLOAD_ERR_NO_FILE:
            echo ("Error: No file was uploaded.");
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            echo ("Error: Missing a temporary folder to store the file.");
            break;
        case UPLOAD_ERR_CANT_WRITE:
            echo ("Error: Failed to write the file to disk.");
            break;
        case UPLOAD_ERR_EXTENSION:
            echo ("Error: A PHP extension stopped the file upload.");
            break;
        default:
            echo "Unknown error code: " . $_FILES['image']['error'];
            break;
    }

    // die("Impossible de déplacer le fichier téléchargé.");
}




// Récupérer le nombre de couleurs
$nbCouleursPalette = isset($_POST['nbCouleurs']) ? (int) $_POST['nbCouleurs'] : 8;
if ($nbCouleursPalette < 2)
    $nbCouleursPalette = 2;
if ($nbCouleursPalette > 256)
    $nbCouleursPalette = 256;

$tailleVignettePalette = 50; // Taille en pixels de chaque couleur dans la palette

// Étape 1: Charger l'image et lister les couleurs présentes
function chargerImage($cheminImage)
{
    $info = getimagesize($cheminImage);
    //echo ($info);
    $type = $info[2];

    switch ($type) {
        case IMAGETYPE_JPEG:
            return imagecreatefromjpeg($cheminImage);
        case IMAGETYPE_PNG:
            return imagecreatefrompng($cheminImage);
        case IMAGETYPE_GIF:
            return imagecreatefromgif($cheminImage);
        default:
            die("Format d'image non supporté");
    }
}

function extraireCouleurs($image)
{
    $largeur = imagesx($image);
    $hauteur = imagesy($image);
    $couleurs = [];

    // Parcourir chaque pixel et compter les couleurs
    for ($y = 0; $y < $hauteur; $y++) {
        for ($x = 0; $x < $largeur; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $couleur = [$r, $g, $b];
            $couleurStr = implode(',', $couleur);

            if (!isset($couleurs[$couleurStr])) {
                $couleurs[$couleurStr] = [
                    'rgb' => $couleur,
                    'count' => 0
                ];
            }

            $couleurs[$couleurStr]['count']++;
        }
    }

    return $couleurs;
}

// Étape 2: Générer une palette de k couleurs (méthode naïve - couleurs les plus fréquentes)
function genererPalette($couleurs, $k)
{
    // Trier les couleurs par fréquence décroissante
    uasort($couleurs, function ($a, $b) {
        return $b['count'] - $a['count'];
    });

    // Prendre les k couleurs les plus fréquentes
    $palette = [];
    $i = 0;
    foreach ($couleurs as $couleur) {
        if ($i >= $k)
            break;
        $palette[] = $couleur['rgb'];
        $i++;
    }

    return $palette;
}

function creerImagePalette($palette, $tailleVignette)
{
    $nbCouleurs = count($palette);
    $largeur = $nbCouleurs * $tailleVignette;
    $hauteur = $tailleVignette;

    $image = imagecreatetruecolor($largeur, $hauteur);

    // Dessiner chaque couleur dans l'image
    for ($i = 0; $i < $nbCouleurs; $i++) {
        $couleur = $palette[$i];
        $colorId = imagecolorallocate($image, $couleur[0], $couleur[1], $couleur[2]);
        imagefilledrectangle(
            $image,
            $i * $tailleVignette,
            0,
            ($i + 1) * $tailleVignette - 1,
            $hauteur - 1,
            $colorId
        );
    }

    return $image;
}




// Étape 3: Recolorier l'image avec la palette réduite


function trouverCouleurProche($couleur, $palette)
{
    $distanceMin = PHP_FLOAT_MAX;
    $couleurProche = null;

    foreach ($palette as $couleurPalette) {
        $distance = distanceEuclidienne($couleur, $couleurPalette);
        if ($distance < $distanceMin) {
            $distanceMin = $distance;
            $couleurProche = $couleurPalette;
        }
    }

    return $couleurProche;
}

function recolorierImage($imageSource, $palette)
{
    $largeur = imagesx($imageSource);
    $hauteur = imagesy($imageSource);

    $imageRecoloriee = imagecreatetruecolor($largeur, $hauteur);

    // Parcourir chaque pixel
    for ($y = 0; $y < $hauteur; $y++) {
        for ($x = 0; $x < $largeur; $x++) {
            $rgb = imagecolorat($imageSource, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Trouver la couleur la plus proche dans la palette
            $couleurProche = trouverCouleurProche([$r, $g, $b], $palette);

            // Appliquer cette couleur au pixel de l'image recoloriée
            $colorId = imagecolorallocate($imageRecoloriee, $couleurProche[0], $couleurProche[1], $couleurProche[2]);
            imagesetpixel($imageRecoloriee, $x, $y, $colorId);
        }
    }

    return $imageRecoloriee;
}




// Étape 4: Calcul d'erreur

function distanceEuclidienne($couleur1, $couleur2)
{
    return
        pow($couleur1[0] - $couleur2[0], 2) +
        pow($couleur1[1] - $couleur2[1], 2) +
        pow($couleur1[2] - $couleur2[2], 2);
}

function erreurImage($imageSource, $imageRecoloriee)
{
    $largeur = imagesx($imageSource);
    $hauteur = imagesy($imageSource);

    $erreur = 0;

    for ($y = 0; $y < $hauteur; $y++) {
        for ($x = 0; $x < $largeur; $x++) {
            $rgbSource = imagecolorat($imageSource, $x, $y);
            $rSource = ($rgbSource >> 16) & 0xFF;
            $gSource = ($rgbSource >> 8) & 0xFF;
            $bSource = $rgbSource & 0xFF;

            $rgbRecoloriee = imagecolorat($imageRecoloriee, $x, $y);
            $rRecoloriee = ($rgbRecoloriee >> 16) & 0xFF;
            $gRecoloriee = ($rgbRecoloriee >> 8) & 0xFF;
            $bRecoloriee = $rgbRecoloriee & 0xFF;

            $erreur += distanceEuclidienne([$rSource, $gSource, $bSource], [$rRecoloriee, $gRecoloriee, $bRecoloriee]);
        }
    }

    return $erreur / (($hauteur * $largeur) * sqrt(3) * 255);
}

// Étape 5 et 7: Amélioration du choix des couleurs et utilisation d'un autre espace colorimetrique 

require __DIR__ . '\conversion.php';
function chargerPixel($imageSource, $space)
{
    $largeur = imagesx($imageSource);
    $hauteur = imagesy($imageSource);

    for ($x = 0; $x < $largeur; $x++) {
        for ($y = 0; $y < $hauteur; $y++) {
            $rgb = imagecolorat($imageSource, $x, $y);

            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $pixel[$y * $largeur + $x] = $space === "lab" ? rgb_to_lab([$r, $g, $b]) : [$r, $g, $b];
        }
    }
    return $pixel;
}

function kMean($imageSource, $k, $space = "rgb", $epsilon = 0.1){
    $pixel = chargerPixel($imageSource, $space);

    $largeur = imagesx($imageSource);
    $hauteur = imagesy($imageSource);

    for ($i = 0; $i < $k; $i++) {
        $centroid[] = $pixel[rand(0, $largeur * $hauteur - 1)];
        $nbr[] = 0;
        $moyenne[] = [0, 0, 0];
    }

    $iteration = 0;
    $maxIterations = 100;

    do {

        for ($i = 0; $i < $k; $i++) {
            $moyenne[$i] = [0, 0, 0];
            $nbr[$i] = 0;
        }

        for ($p = 0; $p < $largeur * $hauteur; $p++) {
            $color = $pixel[$p];
            $kcentroid = 0;
            $dist_min = distanceEuclidienne($color, $centroid[0]);

            for ($i = 1; $i < $k; $i++) {
                $dist = distanceEuclidienne($color, $centroid[$i]);
                if ($dist < $dist_min) {
                    $kcentroid = $i;
                    $dist_min = $dist;
                }
            }

            $moyenne[$kcentroid][0] += $color[0];
            $moyenne[$kcentroid][1] += $color[1];
            $moyenne[$kcentroid][2] += $color[2];

            $nbr[$kcentroid] += 1;
        }

        $variation = 0;
        for ($i = 0; $i < $k; $i++) {
            if ($nbr[$i] != 0) {
                $new_centroid[$i][0] = $moyenne[$i][0] / $nbr[$i];
                $new_centroid[$i][1] = $moyenne[$i][1] / $nbr[$i];
                $new_centroid[$i][2] = $moyenne[$i][2] / $nbr[$i];

                $variation += distanceEuclidienne($centroid[$i], $new_centroid[$i]);

                $centroid[$i] = $new_centroid[$i];
            }

        }

        $iteration += 1;
    } while ($variation > $epsilon && $iteration < $maxIterations);

    if ($space === "lab") {
        for ($i = 0; $i < $k; $i++) {
            $centroid[$i] = lab_to_rgb($centroid[$i]);
        }
    }

    return $centroid;
}


// Exécution du traitement
$image = chargerImage($sourceFile);

// Vérifier la taille de l'image et la redimensionner si elle est trop grande
$maxSize = 800; // Taille maximale en pixels
$largeur = imagesx($image);
$hauteur = imagesy($image);

if ($largeur > $maxSize || $hauteur > $maxSize) {
    if ($largeur > $hauteur) {
        $newLargeur = $maxSize;
        $newHauteur = round($hauteur * ($maxSize / $largeur));
    } else {
        $newHauteur = $maxSize;
        $newLargeur = round($largeur * ($maxSize / $hauteur));
    }

    $imageRedim = imagecreatetruecolor($newLargeur, $newHauteur);
    imagecopyresampled($imageRedim, $image, 0, 0, 0, 0, $newLargeur, $newHauteur, $largeur, $hauteur);
    imagedestroy($image);
    $image = $imageRedim;

    // Sauvegarder la version redimensionnée
    imagejpeg($image, $sourceFile);
}

//RGB k-mean

// generer la palette
$palette_kmean = kMean($image, $nbCouleursPalette);
$imagePalette_kmean = creerImagePalette($palette_kmean, $tailleVignettePalette);

// Recolorier l'image
$imageRecoloriee_kmean = recolorierImage($image, $palette_kmean);

// Sauvegarder les résultats
imagejpeg($imagePalette_kmean, "$outputDir/palette_kmean.jpg");
imagejpeg($imageRecoloriee_kmean, "$outputDir/recoloriee_kmean.jpg");

$erreur_kmean = erreurImage($image, $imageRecoloriee_kmean);


// LAB k-mean

// generer la palette
$palette_kmean_lab = kMean($image, $nbCouleursPalette, "lab");
$imagePalette_kmean_lab = creerImagePalette($palette_kmean_lab, $tailleVignettePalette);

// Recolorier l'image
$imageRecoloriee_kmean_lab = recolorierImage($image, $palette_kmean_lab);

// Sauvegarder les résultats
imagejpeg($imagePalette_kmean_lab, "$outputDir/palette_kmean_lab.jpg");
imagejpeg($imageRecoloriee_kmean_lab, "$outputDir/recoloriee_kmean_lab.jpg");

$erreur_kmean_lab = erreurImage($image, $imageRecoloriee_kmean_lab);

// Solution naive

$couleurs = extraireCouleurs($image);

$palette = genererPalette($couleurs, $nbCouleursPalette);
$imagePalette = creerImagePalette($palette, $tailleVignettePalette);

// Recolorier l'image
$imageRecoloriee = recolorierImage($image, $palette);

// Sauvegarder les résultats
imagejpeg($imagePalette, "$outputDir/palette.jpg");
imagejpeg($imageRecoloriee, "$outputDir/recoloriee.jpg");

$erreur = erreurImage($image, $imageRecoloriee);

// Comparaison avec Imagick

$original = new Imagick('C:/xampp/htdocs/Projet/CodeProjet/uploads/source.jpg');
// Créer une copie pour la quantization
$quantized = clone $original;
// Appliquer la quantization (réduction à nbcouleur couleurs)
$quantized->quantizeImage($nbCouleursPalette, Imagick::COLORSPACE_RGB, 0, false, true);

// Sauvegarder l'image quantifiée
$quantized->writeImage('C:/xampp/htdocs/Projet/CodeProjet/output/quantized.jpg');

$quantized2 = chargerImage('C:/xampp/htdocs/Projet/CodeProjet/output/quantized.jpg');

$erreur_quantized = erreurImage($image, $quantized2);


// Libérer la mémoire
imagedestroy($image);
imagedestroy($imagePalette_kmean);
imagedestroy($imageRecoloriee_kmean);
imagedestroy($imagePalette_kmean_lab);
imagedestroy($imageRecoloriee_kmean_lab);
imagedestroy($imagePalette);
imagedestroy($imageRecoloriee);



// Rediriger vers la page de résultats
header("Location: index.php?processed=true&nbCouleurs=$nbCouleursPalette&erreur=$erreur&erreur_kmean=$erreur_kmean&erreur_quantized=$erreur_quantized&erreur_kmean_lab=$erreur_kmean_lab,");


exit;
?>