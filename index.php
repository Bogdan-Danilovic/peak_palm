<?php
// Uključujemo naš fajl za konekciju sa bazom
require_once 'db.php';

try {
    // Pišemo SQL upit da izvučemo sve destinacije iz baze
    $stmt = $pdo->query("SELECT * FROM destinacije");
    $sveDestinacije = $stmt->fetchAll();
    
    // Ako smo došli do ovde, konekcija radi!
    $konekcijaUspesna = true;
} catch (PDOException $e) {
    $konekcijaUspesna = false;
    $greska = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peak & Palm - Test Konekcije</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0b132b;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: radial-gradient(circle at 50% 50%, rgba(0, 180, 216, 0.15) 0%, transparent 60%);
        }
        .prozor {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #ffffff; 
            margin-bottom: 20px;
        }
        .uspeh { 
            color: #00ff88; 
            font-weight: 600; 
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .greska { 
            color: #ff0055; 
            font-weight: 600; 
            font-size: 1.2rem;
        }
        .highlight { 
            color: #00b4d8; 
            font-weight: 700; 
            font-size: 1.5rem; 
            display: block;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="prozor">
        <h1>Peak & Palm Sistem</h1>
        
        <?php if ($konekcijaUspesna): ?>
            <p class="uspeh">✅ Konekcija sa MySQL bazom je USPEŠNA!</p>
            <p>Pronađeno destinacija u bazi: <strong><?php echo count($sveDestinacije); ?></strong></p>
            
            <?php if(count($sveDestinacije) > 0): ?>
                <p>Prva destinacija učitana iz baze je: 
                <span class="highlight">
                    <?php echo htmlspecialchars($sveDestinacije[0]['naziv']); ?> 
                    (<?php echo htmlspecialchars($sveDestinacije[0]['drzava']); ?>)
                </span></p>
            <?php endif; ?>

        <?php else: ?>
            <p class="greska">❌ Greška u konekciji:</p>
            <p><?php echo htmlspecialchars($greska); ?></p>
        <?php endif; ?>
    </div>

</body>
</html>