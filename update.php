<?php
session_start();
require_once "connect.php";

// Nawiązanie połączenia z bazą danych na podstawie danych z pliku connect.php
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

$url_profilowe = '';
$opis = '';

// Pobranie URL profilowego i opisu użytkownika
$sqlFetchUrlOpis = "SELECT `url_profilowe`, `opis` FROM `profiluzytkownika` WHERE `idUzytkownik` = (SELECT `idUzytkownicy` FROM `uzytkownicy` WHERE `login` = ?)";
if ($stmt = $conn->prepare($sqlFetchUrlOpis)) {
    $stmt->bind_param("s", $_SESSION["login"]);
    $stmt->execute();
    $stmt->bind_result($url_profilowe, $opis);
    $stmt->fetch();
    $stmt->close();
}

// Ustawienie domyślnego URL profilowego, jeśli jest pusty
if (empty($url_profilowe)) {
    $url_profilowe = 'https://placehold.jp/50x50.png';
}

// Aktualizacja URL profilowego użytkownika
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["new_url"])) {
        $new_url = $_POST["new_url"];
        $sqlurl = "UPDATE `profiluzytkownika` SET `url_profilowe` = ? WHERE `idUzytkownik` = (SELECT `idUzytkownicy` FROM `uzytkownicy` WHERE `login` = ?)";
        if($stmt = $conn->prepare($sqlurl)){
            $stmt->bind_param("ss", $new_url, $_SESSION["login"]);
            $stmt->execute();
            $url_profilowe = $new_url;
        }
    }

    if (isset($_POST["new_opis"])) {
        $new_opis = $_POST["new_opis"];
        $sqlopis = "UPDATE `profiluzytkownika` SET `opis` = ? WHERE `idUzytkownik` = (SELECT `idUzytkownicy` FROM `uzytkownicy` WHERE `login` = ?)";
        if($stmt = $conn->prepare($sqlopis)){
            $stmt->bind_param("ss", $new_opis, $_SESSION["login"]);
            $stmt->execute();
            $opis = $new_opis;
        }
    }
}

?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGRVideo</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
    <div class="d-flex align-items-center">
      <a class="navbar-brand" href="index.php">NGRVideo</a>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="datamovies.php">Baza filmów</a>
        </li>
      </ul>
      <form class="d-flex mx-3" action="search.php" method="POST" role="search">
        <input class="form-control me-2" type="search" name="search" placeholder="Search" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
      </form>
    </div>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
          <?php
            if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
              echo '<li class="navbar-text">Witaj, niezalogowany</li>';
              echo '<li class="nav-item"><a class="nav-link" href="login.php">Logowanie</a></li>';
              echo '<li class="nav-item"><a class="nav-link" href="register.php">Rejestracja</a></li>';
            } 
            else {
              echo '<li class="navbar-text">Witaj, '.$_SESSION["login"].'</li>';
              echo '<li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>';
              echo '<li class="nav-item"><a class="nav-link" href="logout.php">Wyloguj się</a></li>';
            
            }
          ?>
        </ul>

    </div>
  </div>
</nav>
<div class="container">
    <div class="main-body">
        <div class="container">
            <div class="main-body">
                <div class="row mt-5">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <img  id="pfpic" src="<?php echo htmlspecialchars($url_profilowe); ?>" alt="Błędny URL" class="rounded-circle shadow-4" width="150">
                                    <div class="mt-3">
                                        <h4><?php
                      echo $_SESSION["login"];
                      ?></h4>
                                    </div>
                                </div>
                                <form method="post" action="update.php">
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text" id="addon-wrapping">URL</span>
                                        <input id="input"  type="text" name="new_url" class="form-control" autocomplete="off">
                                        <button id="buttonchange" type="submit" class="btn btn-primary">Zmień</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <form method="post" action="update.php">
                                        <div class="col-sm-3">
                                            <h6 class="mb-0">Opis</h6>
                                        </div>
                                        <div class="col-sm-9 text-secondary">
                                            <input type="text" name="new_opis" class="form-control" value="<?php echo htmlspecialchars($opis); ?>">
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-3"></div>
                                            <div class="col-sm-9 text-secondary">
                                                <input type="submit" class="btn btn-primary px-4" value="Zapisz zmiany">
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>
