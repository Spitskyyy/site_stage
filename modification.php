<?php
session_start();
require 'vendor/autoload.php';

// Charger les variables d'environnement à partir du fichier .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Récupérer les variables d'environnement
$servername = $_ENV['BD_HOST'];
$username = $_ENV['BD_USER'];
$password = $_ENV['BD_PASS'];
$dbname = $_ENV['BD_NAME'];

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}

// Vérifier si l'ID du produit est présent dans l'URL
if (!isset($_GET['id_product']) || empty($_GET['id_product'])) {
    die("ID de produit manquant.");
}

$id_product = $_GET['id_product'];

// Récupérer les données actuelles du produit
$query = "SELECT tbl_product.*, tbl_dimension.length_dimension, tbl_dimension.width_dimension, tbl_dimension.thickness_dimension 
          FROM tbl_product 
          JOIN tbl_product_type_of_product ON tbl_product.id_product = tbl_product_type_of_product.id_product_product
          JOIN tbl_dimension ON tbl_product_type_of_product.id_dimension_dimension = tbl_dimension.id_dimension
          WHERE tbl_product.id_product = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_product);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Produit non trouvé.");
}

$product = $result->fetch_assoc();

// Mettre à jour les données du produit si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_product = $_POST['name_product'];
    $essence_product = $_POST['essence_product'];
    $length_dimension = $_POST['length_dimension'];
    $width_dimension = $_POST['width_dimension'];
    $thickness_dimension = $_POST['thickness_dimension'];
    $quantity_product = $_POST['quantity_product'];
    $description_product = $_POST['description_product'];
    $image_path_product = $_POST['image_path_product'];

    // Mettre à jour les informations du produit
    $update_product_query = "UPDATE tbl_product SET name_product = ?, essence_product = ?, quantity_product = ?, description_product = ?, image_path_product = ? WHERE id_product = ?";
    $stmt = $conn->prepare($update_product_query);
    $stmt->bind_param("ssissi", $name_product, $essence_product, $quantity_product, $description_product, $image_path_product, $id_product);
    
    // Mettre à jour les dimensions
    $update_dimension_query = "UPDATE tbl_dimension 
                               JOIN tbl_product_type_of_product ON tbl_dimension.id_dimension = tbl_product_type_of_product.id_dimension_dimension
                               SET length_dimension = ?, width_dimension = ?, thickness_dimension = ?
                               WHERE tbl_product_type_of_product.id_product_product = ?";
    $stmt_dimension = $conn->prepare($update_dimension_query);
    $stmt_dimension->bind_param("dddi", $length_dimension, $width_dimension, $thickness_dimension, $id_product);

    if ($stmt->execute() && $stmt_dimension->execute()) {
        // Redirection vers la même page avec un paramètre de succès
        header('location: /bardage.php');
        exit();
    } else {
        echo "Erreur lors de la mise à jour du produit : " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Produit</title>
    <link rel="stylesheet" href="styles.css"> <!-- Assurez-vous que le chemin vers votre fichier CSS est correct -->
</head>
<body>
    <h1>Modifier Produit</h1>
    <?php if (isset($_GET['success'])): ?>
        <p style="color: green;">Produit mis à jour avec succès.</p>
    <?php endif; ?>
    <form method="post" action="modification.php?id_product=<?php echo htmlspecialchars($id_product); ?>">
        <label for="name_product">Nom du produit:</label>
        <input type="text" id="name_product" name="name_product" value="<?php echo htmlspecialchars($product['name_product'] ?? ''); ?>" required><br>

        <label for="essence_product">Essence du produit:</label>
        <input type="text" id="essence_product" name="essence_product" value="<?php echo htmlspecialchars($product['essence_product'] ?? ''); ?>" required><br>

        <label for="length_dimension">Longueur (cm):</label>
        <input type="number" id="length_dimension" name="length_dimension" value="<?php echo htmlspecialchars($product['length_dimension'] ?? ''); ?>" required><br>

        <label for="width_dimension">Largeur (cm):</label>
        <input type="number" id="width_dimension" name="width_dimension" value="<?php echo htmlspecialchars($product['width_dimension'] ?? ''); ?>" required><br>

        <label for="thickness_dimension">Épaisseur (cm):</label>
        <input type="number" id="thickness_dimension" name="thickness_dimension" value="<?php echo htmlspecialchars($product['thickness_dimension'] ?? ''); ?>" required><br>

        <label for="quantity_product">Quantité:</label>
        <input type="number" id="quantity_product" name="quantity_product" value="<?php echo htmlspecialchars($product['quantity_product'] ?? ''); ?>" required><br>

        <label for="description_product">Description:</label>
        <textarea id="description_product" name="description_product" required><?php echo htmlspecialchars($product['description_product'] ?? ''); ?></textarea><br>

        <label for="image_path_product">Chemin de l'image:</label>
        <input type="text" id="image_path_product" name="image_path_product" value="<?php echo htmlspecialchars($product['image_path_product'] ?? ''); ?>"><br>

        <input type="submit" value="Mettre à jour">
    </form>
</body>
</html>




<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    h1 {
        text-align: center;
        color: #333;
        margin-top: 20px;
        /* Ajout d'une marge pour espacer le titre du haut de la page */
    }

    form {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        max-width: 400px;
        width: 100%;
        box-sizing: border-box;
        margin-top: 20px;
        /* Ajout d'une marge pour espacer le formulaire du titre */
    }

    form label {
        display: block;
        margin-bottom: 5px;
        color: #333;
    }

    form input[type="text"],
    form input[type="number"],
    form textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }

    form textarea {
        resize: vertical;
        height: 100px;
    }

    form input[type="submit"] {
        background: #5cb85c;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }

    form input[type="submit"]:hover {
        background: #4cae4c;
    }
</style>

</html>