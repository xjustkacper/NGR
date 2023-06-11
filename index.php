<?php
// Rozpoczęcie sesji, które umożliwia przechowywanie danych w czasie trwania sesji.
session_start();

// Dołączenie pliku zawierającego dane do połączenia z bazą danych.
require_once "connect.php";

// Utworzenie nowego obiektu MySQLi używając danych z pliku connect.php
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// SQL zapytanie do pobrania top 10 filmów z najwyższymi ocenami.
$sqltop10 = "SELECT f.Tytul,f.url_baner, AVG(o.LiczbaGwiazdek) AS SredniaOcena,f.idFilmy FROM filmy f JOIN oceny o ON f.idFilmy = o.idFilmy GROUP BY f.idFilmy, f.Tytul ORDER BY SredniaOcena DESC LIMIT 10;"; 

// SQL zapytanie do pobrania 10 najnowszych filmów.
$sql2 = "SELECT * FROM `filmy` ORDER BY idFilmy DESC LIMIT 10;";

// Przygotowanie i wykonanie pierwszego zapytania SQL.
$stmt = $conn->prepare($sqltop10);
$stmt->execute();
$result = $stmt->get_result();

// Zapisanie wyników zapytania do tablicy.
$tytul = [];
while ($row = $result->fetch_assoc()) {
    $tytul[] = $row;
}

// Przygotowanie i wykonanie drugiego zapytania SQL.
$stmt2 = $conn->prepare($sql2);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Zapisanie wyników drugiego zapytania do tablicy.
$najnowsze = [];
while ($row = $result2->fetch_assoc()) {
    $najnowsze[] = $row;
}

// Losowanie id reklamy.
$idReklama = rand(1, 2);  // Zakładam, że id reklam są od 1 do 2.

// Zapytanie do pobrania reklamy.
$sqlReklama = "SELECT url_reklama FROM reklama WHERE idReklama = ?";
$stmtReklama = $conn->prepare($sqlReklama);
$stmtReklama->bind_param("i", $idReklama);
$stmtReklama->execute();
$resultReklama = $stmtReklama->get_result();

// Zapisanie wyników zapytania o reklamę do tablicy.
$reklama = [];
while ($row = $resultReklama->fetch_assoc()) {
    $reklama[] = $row;
}

// Zamknięcie wszystkich zapytań i połączenia z bazą danych.
$stmtReklama->close();
$stmt->close();
$stmt2->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGRVideo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <!-- Do karuzeli  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css"
        integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g=="
        crossorigin="anonymous" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css"
        integrity="sha512-sMXtMNL1zRzolHYKEujM2AqCLUR9F2C4/05cdbxjjLSRvMQIciEPCQZo++nk7go3BtSuK9kfa/s+a4f4i5pLkw=="
        crossorigin="anonymous" />
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




<section>
  <div class="container-fluid my-5" >
    <h1 class="text-center fw-bold ">TOP 10</h1>
    <div class="row">
    <div class="owl-carousel owl-theme">
    <?php 
    if (count($tytul) > 0) {
        foreach ($tytul as $tytuly): ?>
            <div class="item">
              <div class="card">
                <a href="moviepage.php?id=<?php echo $tytuly["idFilmy"] ?>">
                  <img src="<?php echo $tytuly["url_baner"]?>" alt="image" class="card-img-top">
                </a>
                <div class="card-body">
                  <h4><?php echo $tytuly["Tytul"]?></h4>
                </div>
              </div>
            </div>
        <?php endforeach;
    } 
    ?>
    </div>
    </div>
</div>
</div>
</section>
<section>
  <div class="container-fluid my-5" >
    <h1 class="text-center fw-bold ">Nowe</h1>
    <div class="row">
      <div class="owl-carousel owl-theme">
      <?php 
    if (count($najnowsze) > 0) {
        foreach ($najnowsze as $film): ?>
            <div class="item">
              <div class="card">
                <a href="moviepage.php?id=<?php echo $film["idFilmy"] ?>">
                  <img src="<?php echo $film["url_baner"]?>" alt="image" class="card-img-top" >
                </a>
                <div class="card-body">
                  <h4><?php echo $film["Tytul"]?></h4>
                </div>
              </div>
            </div>
        <?php endforeach;
    } 
?>
      </div>
    </div>
  </div>
</section>

<div>
  <br />
    
  <div class="d-flex justify-content-center">
    <!-- Wyświetlanie reklamy -->
    <?php if (count($reklama) > 0) {
        foreach ($reklama as $rek): ?>
            <a href="register.php"><img src="<?php echo $rek["url_reklama"]?>" alt="Reklama"> </a>
        <?php endforeach;
    } ?>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Option 1: Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4"
    crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"
    integrity="sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw=="
    crossorigin="anonymous"></script>
<script>
    $('.owl-carousel').owlCarousel({
        loop: true,
        margin: 15,
        nav: true,
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 2
            },
            1000: {
                items: 5
            }
        }
    })
</script>
</body>
</html>
