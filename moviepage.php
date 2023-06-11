<?php
session_start();
require_once "connect.php";

// Sprawdzenie, czy użytkownik jest zalogowany
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
  header("Location: login.php");
  exit;
} 

// Pobranie loginu użytkownika, jeśli jest zalogowany
if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true){
  $userLogin = $_SESSION['login'];
} else {
  $userLogin = '';
}

// Pobranie identyfikatora filmu, jeśli jest przekazywany w parametrze GET
if (isset($_GET['id'])) {
  $idFilmy = $_GET['id'];
} 

// Pobranie szczegółowych informacji o filmie z bazy danych
$conn = new mysqli($host, $db_user, $db_pass, $db_name);
$sql = "SELECT f.idFilmy, l.url AS 'Link', f.Tytul, f.opis, f.rezyser, f.rokprodukcji, f.czastrwania, k.Nazwa AS 'kategoria', f.Jezyk, f.url_baner 
FROM filmy f 
INNER JOIN linki l ON f.idLinki = l.idLinki 
INNER JOIN kategorie k ON f.idKategoria = k.idKategorie 
WHERE f.idFilmy = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idFilmy);
$stmt->execute();
$result = $stmt->get_result();

// Zakładamy, że zawsze znajdziemy film o danym identyfikatorze
$film = $result->fetch_assoc();

$stmt->close();
$movieId = $idFilmy;

// Pobranie ID profilu użytkownika na podstawie loginu
$stmt = $conn->prepare("SELECT idProfilUzytkownika FROM ProfilUzytkownika INNER JOIN uzytkownicy ON ProfilUzytkownika.idUzytkownik = uzytkownicy.idUzytkownicy WHERE login = ?");
$stmt->bind_param("s", $userLogin);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$userId = $row['idProfilUzytkownika'];  // Pobranie ID profilu użytkownika z wyniku zapytania

// Pobranie oceny użytkownika dla danego filmu
$stmt = $conn->prepare("SELECT LiczbaGwiazdek FROM oceny WHERE idFilmy=? AND idProfilUzytkownika=?");
$stmt->bind_param("ii", $movieId, $userId);
$stmt->execute();
$resultt = $stmt->get_result();

// Jeśli istnieje ocena użytkownika dla tego filmu, przypisz ją do zmiennej $numberofstars, w przeciwnym razie przypisz 0
if($resultt->num_rows > 0) {
    $row = $resultt->fetch_assoc();
    $numberofstars = $row['LiczbaGwiazdek'];
} else {
    $numberofstars = 0;
}
$stmt->close();

// Obsługa dodawania komentarza
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
  $komentarz = $_POST['comment'];

  // Dodanie komentarza do bazy danych
  $stmt = $conn->prepare("INSERT INTO komentarze (idProfilUzytkownika, idFilmy, komentarz) VALUES (?, ?, ?)");
  $stmt->bind_param("iis", $userId, $idFilmy, $komentarz);
  $stmt->execute(); 
  $stmt->close();
}

// Pobranie komentarzy dla danego filmu
$sqlk = "SELECT komentarze.*, uzytkownicy.login, profiluzytkownika.url_profilowe FROM komentarze JOIN profiluzytkownika ON komentarze.idProfilUzytkownika = profiluzytkownika.idProfilUzytkownika JOIN uzytkownicy ON profiluzytkownika.idUzytkownik = uzytkownicy.idUzytkownicy WHERE komentarze.idFilmy = ? ORDER BY komentarze.idKomentarze DESC";

if ($stmt = $conn->prepare($sqlk)) {
  $stmt->bind_param("i", $idFilmy);
  $stmt->execute();
  $result = $stmt->get_result();
  $comments = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
} else {
  echo "Błąd: " . $conn->error;
}

// Obsługa oceny filmu przez użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
  $rating = $_POST['rating'];
  
  // Sprawdzenie, czy użytkownik już ocenił ten film
  $stmt = $conn->prepare("SELECT * FROM oceny WHERE idFilmy=? AND idProfilUzytkownika=?");
  $stmt->bind_param("ii", $movieId, $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  // Jeśli użytkownik już ocenił, zaktualizuj ocenę w bazie danych, w przeciwnym razie dodaj nową ocenę
  if ($result->num_rows > 0) {
      $stmt = $conn->prepare("UPDATE oceny SET LiczbaGwiazdek=? WHERE idFilmy=? AND idProfilUzytkownika=?");
      $stmt->bind_param("iii", $rating, $movieId, $userId);
  } else {
      $stmt = $conn->prepare("INSERT INTO oceny (idFilmy, idProfilUzytkownika, LiczbaGwiazdek) VALUES (?, ?, ?)");
      $stmt->bind_param("iii", $movieId, $userId, $rating);
  }

  $stmt->execute(); 
  $stmt->close();

  // Pobranie aktualnej oceny użytkownika dla filmu
  $stmt = $conn->prepare("SELECT LiczbaGwiazdek FROM oceny WHERE idFilmy=? AND idProfilUzytkownika=?");
  $stmt->bind_param("ii", $movieId, $userId);
  $stmt->execute();
  $resultt = $stmt->get_result();

  // Jeśli istnieje ocena użytkownika dla tego filmu, przypisz ją do zmiennej $numberofstars, w przeciwnym razie przypisz 0
  if($resultt->num_rows > 0) {
    $row = $resultt->fetch_assoc();
    $numberofstars = $row['LiczbaGwiazdek'];
  } else {
    $numberofstars = 0;
  }
  $stmt->close();

  echo $numberofstars;
  exit();
}

// Obsługa dodawania/usuwanie filmu z ulubionych
if(isset($_POST['toggle-favorite'])) {
  $check = $conn->prepare("SELECT * FROM ulubioneFilmy WHERE idFilmy=? AND idProfilUzytkownika=?");
  $check->bind_param('ii', $idFilmy, $userId);
  $check->execute();
  $result = $check->get_result();

  // Jeśli film nie jest w ulubionych, dodaj go; w przeciwnym razie usuń z ulubionych
  if($result->num_rows === 0) {
      $stmt = $conn->prepare("INSERT INTO ulubioneFilmy (idFilmy, idProfilUzytkownika) VALUES (?, ?)");
      $stmt->bind_param('ii', $idFilmy, $userId);
  } else {
      $stmt = $conn->prepare("DELETE FROM ulubioneFilmy WHERE idFilmy=? AND idProfilUzytkownika=?");
      $stmt->bind_param('ii', $idFilmy, $userId);
  }
  $stmt->execute();
}

// Sprawdzenie, czy film jest w ulubionych dla danego użytkownika
$check = $conn->prepare("SELECT * FROM ulubioneFilmy WHERE idFilmy=? AND idProfilUzytkownika=?");
$check->bind_param('ii', $idFilmy, $userId);
$check->execute();
$result = $check->get_result();
$favorite = $result->num_rows !== 0;

$conn->close();
?>



<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGRVideo</title>
    <link rel="stylesheet" href="starss.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/730c223759.js" crossorigin="anonymous"></script>
    
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
    <div class="main-body pt-5">  
      <div class="card">
        <div class="card-body">
        <div class="row gutters-sm mt-5 justify-content-center">
            <div class="col-md-8">
              <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                      <div class="col-sm-3">
                        <h6 class="mb-0">Tytuł:</h6>
                      </div>
                      <div class="col-sm-9 text-secondary">
                      <?php
                        echo $film['Tytul']
                        ?>
                      </div>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-sm-3">
                        <h6 class="mb-0">Kategoria:</h6>
                      </div>
                      <div class="col-sm-9 text-secondary">
                        <?php
                      echo $film['kategoria'];  
                      ?> 
                      </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                          <h6 class="mb-0">Opis:</h6>
                        </div>
                        <div class="col-sm-9 text-secondary">
                            <?php 
                            echo $film['opis']; 
                            ?>
                      </div>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-sm-3">
                        <h6 class="mb-0">Reżyser:</h6>
                      </div>
                      <div class="col-sm-9 text-secondary">
                      <?php
                        echo $film['rezyser']
                        ?>
                      </div>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-sm-3">
                        <h6 class="mb-0">Czas trwania:</h6>
                      </div>
                      <div class="col-sm-9 text-secondary">
                      <?php
                        echo $film['czastrwania']
                        ?> minut
                      </div>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-sm-3">
                        <h6 class="mb-0">Rok produkcji:</h6>
                      </div>
                      <div class="col-sm-9 text-secondary">
                      <?php
                        echo $film['rokprodukcji']
                        ?>
                      </div>
                    </div>
                    <hr>
                    <div class="row">
    <div class="col-sm-3">
        <h6 class="mb-0">Ocena:</h6>
    </div>
    <div class="col-sm-9 text-secondary">
        <form id="rating-form" action="moviepage.php?id=<?php echo $idFilmy; ?>" method="POST">
        <input type="hidden" id="rating-input" name="rating" value="<?php echo $numberofstars ?>">
        <div class="rating">
            <span class="rating__star" data-value="1">&#9734;</span>
            <span class="rating__star" data-value="2">&#9734;</span>
            <span class="rating__star" data-value="3">&#9734;</span>
            <span class="rating__star" data-value="4">&#9734;</span>
            <span class="rating__star" data-value="5">&#9734;</span>
            <span id="star-count"><?php echo $numberofstars ?></span>
        </div>
    </form>
    </div>
</div>
                           </div>
                        </div>
                        <form method="post">
<button type="submit" class="btn btn-warning" name="toggle-favorite">
    <?php echo $favorite ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'; ?>
</button>
</form>
                     </div>
                     <div class="row justify-content-center mt-5">
                        <div class="col-md-auto">
                           <iframe width="854" height="480" src="<?php echo $film['Link']?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="container mt-5 mb-3 text-dark">
        <div class="row d-flex justify-content-center">
          <div class="col-md-10 col-lg-8">
            <div class="card">
              <div class="card-body p-4">
                <div class="d-flex flex-start w-100">
                  <div class="w-100">
                    <h5>Dodaj komentarz</h5>
                    <form method="post" action="moviepage.php?id=<?php echo $idFilmy; ?>">
                        <div class="form-outline">
                          <textarea class="form-control" id="textAreaExample" rows="4" name="comment"></textarea>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                          <button type="submit" class="btn btn-danger">
                            Wyślij <i class="fas fa-long-arrow-alt-right ms-1"></i>
                          </button>
                        </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="container">
    <?php foreach ($comments as $comment) : ?>
    <div class="row mb-2 justify-content-center">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-2">
                            <img class="rounded-circle shadow-1-strong me-3"
                                 src="<?= $comment['url_profilowe'] ?>" alt="avatar" width="65"
                                 height="65" />
                        </div>
                        <div class="col my-3">
                            <?= $comment['login'] ?>
                        </div>
                    </div>
                    <div class="row mt-3 justify-content-center">
                        <div class="col-sm-9">
                            <div class="card">
                                <div class="card-body">
                                    <?= htmlspecialchars($comment['komentarz']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
      <script src="rating.js"></script>
   </body>
</html>
