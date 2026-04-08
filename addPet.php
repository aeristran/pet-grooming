<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "CUSTOMER") {
    header("location: login.php");
    exit;
}

$pet_name = $species = $breed = "";
$pet_name_err = $species_err = $image_err = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["pet_name"]))) {
        $pet_name_err = "Please enter pet name.";
    } else {
        $pet_name = trim($_POST["pet_name"]);
    }

    if (empty(trim($_POST["species"]))) {
        $species_err = "Please select species.";
    } else {
        $species = trim($_POST["species"]);
    }

    $breed = trim($_POST["breed"]);
    $pet_image_path = null;

    if (isset($_FILES["pet_image"]) && $_FILES["pet_image"]["error"] == 0) {
        $allowed_types = ["image/jpeg", "image/png", "image/jpg"];
        $file_type = $_FILES["pet_image"]["type"];

        if (in_array($file_type, $allowed_types)) {
            $file_name = time() . "_" . basename($_FILES["pet_image"]["name"]);
            $target_path = "uploads/" . $file_name;

            if (move_uploaded_file($_FILES["pet_image"]["tmp_name"], $target_path)) {
                $pet_image_path = $target_path;
            } else {
                $image_err = "Image upload failed.";
            }
        } else {
            $image_err = "Only JPG, JPEG, and PNG files are allowed.";
        }
    }

    if (empty($pet_name_err) && empty($species_err) && empty($image_err)) {
        $sql = "INSERT INTO pets (customer_id, pet_name, species, breed, pet_image) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issss", $param_customer_id, $param_pet_name, $param_species, $param_breed, $param_pet_image);

            $param_customer_id = $_SESSION["user_id"];
            $param_pet_name = $pet_name;
            $param_species = $species;
            $param_breed = $breed;
            $param_pet_image = $pet_image_path;

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Pet added successfully!";
                $pet_name = $species = $breed = "";
            } else {
                echo "Something went wrong. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Pet</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 420px; padding: 20px; margin: 40px auto; }
    </style>
</head>
<body class="w3-light-grey">

<div class="wrapper w3-white w3-border w3-round-large">
    <h2>Add Pet</h2>
    <p>Enter your pet information below.</p>

    <?php if (!empty($success_msg)) { ?>
        <div class="w3-panel w3-green"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php } ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
        <div class="w3-container">
            <label>Pet Name</label>
            <input type="text" name="pet_name" class="w3-input" value="<?php echo htmlspecialchars($pet_name); ?>">
            <span class="w3-text-red"><?php echo $pet_name_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Species</label>
            <select name="species" class="w3-select">
                <option value="">Select species</option>
                <option value="DOG">Dog</option>
                <option value="CAT">Cat</option>
                <option value="OTHER">Other</option>
            </select>
            <span class="w3-text-red"><?php echo $species_err; ?></span>
        </div>

        <div class="w3-container">
            <label>Breed</label>
            <input type="text" name="breed" class="w3-input" value="<?php echo htmlspecialchars($breed); ?>">
        </div>

        <div class="w3-container">
            <label>Pet Image</label>
            <input type="file" name="pet_image" class="w3-input">
            <span class="w3-text-red"><?php echo $image_err; ?></span>
        </div>

        <div class="w3-container w3-margin-top">
            <input type="submit" class="w3-button w3-green" value="Add Pet">
            <a href="customer_dashboard.php" class="w3-button w3-gray">Back</a>
        </div>
    </form>
</div>

</body>
</html>