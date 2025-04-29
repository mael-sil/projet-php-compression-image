<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantification des Couleurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        form {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select {
            margin-bottom: 15px;
            padding: 8px;
            width: 100%;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .results {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }
        .result-item {
            flex: 1;
            min-width: 250px;
        }
        .result-item img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quantification des Couleurs d'une Image</h1>
        
        <form action="process.php" method="post" enctype="multipart/form-data">
            <label for="image">Sélectionner une image :</label>
            <input type="file" id="image" name="image" accept="image/*" required>
            
            <label for="nbCouleurs">Nombre de couleurs dans la palette :</label>
            <input type="number" id="nbCouleurs" name="nbCouleurs" min="2" max="256" value="8" required>
            
            <button type="submit">Analyser l'image</button>
        </form>
        
        <?php if (isset($_GET['processed']) && $_GET['processed'] === 'true'): ?>
        <div class="results">
            <div class="result-item">
                <h2>Image originale</h2>
                <img src="./uploads/source.jpg" alt="Image originale">
            </div>
            
            <div class="result-item">
            <h2>Palette de <?php echo isset($_GET['nbCouleurs']) ? $_GET['nbCouleurs'] : '8'; ?> couleurs représentatives avec le k-mean en RGB</h2>
            <img src="./output/palette_kmean.jpg" alt="Palette de couleurs">
            <h2>Palette de <?php echo isset($_GET['nbCouleurs']) ? $_GET['nbCouleurs'] : '8'; ?> couleurs représentatives avec le k-mean en LAB</h2>
            <img src="./output/palette_kmean_lab.jpg" alt="Palette de couleurs">
                <h2>Palette de <?php echo isset($_GET['nbCouleurs']) ? $_GET['nbCouleurs'] : '8'; ?> couleurs représentatives avec la methode naive</h2>
                <img src="./output/palette.jpg" alt="Palette de couleurs">
            </div>
            
            <div class="result-item">
                <h2>Image recoloriée avec k-mean en RGB</h2>
                <img src="./output/recoloriee_kmean.jpg" alt="Image recoloriée avec k-mean">
                <p>Erreur: <?php echo $_GET['erreur_kmean'] ?> </p>
                <h2>Image recoloriée avec k-mean en LAB</h2>
                <img src="./output/recoloriee_kmean_lab.jpg" alt="Image recoloriée naive">
                <p>Erreur: <?php echo $_GET['erreur_kmean_lab'] ?> </p>
                <h2>Image recoloriée avec methode naive</h2>
                <img src="./output/recoloriee.jpg" alt="Image recoloriée naive">
                <p>Erreur: <?php echo $_GET['erreur'] ?> </p>
                <h2>Image recoloriée avec quantizeImage de imagick</h2>
                <img src="./output/quantized.jpg" alt="Image recoloriée naive">
                <p>Erreur: <?php echo $_GET['erreur_quantized'] ?> </p>
                
                
            </div>

        
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
