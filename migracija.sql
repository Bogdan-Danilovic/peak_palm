-- ============================================================================
-- Peak & Palm — Maturski rad
-- Univerzalna sema baze za katalog destinacija (skijanje / letovanje).
-- ----------------------------------------------------------------------------
-- Filozofija:
--   * 7 fokusiranih tabela, sve povezane na `destinacije` preko stranog kljuca.
--   * Dodavanje nove destinacije = INSERT redovi kroz phpMyAdmin.
--   * Nije potreban admin panel — frontend automatski iscrtava sve podatke.
--   * SVG staze + JSON polja = pun dinamicki sablon koji radi i za zimu i za leto.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1) GRANICNI PRELAZI  (globalna pomocna tabela)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `granicni_prelazi`;
CREATE TABLE `granicni_prelazi` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `naziv`               VARCHAR(80)  NOT NULL,
    `iz_drzave`           VARCHAR(40)  NOT NULL DEFAULT 'Srbija',
    `u_drzavu`            VARCHAR(40)  NOT NULL,
    `tipicno_cekanje_min` SMALLINT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2) DESTINACIJE  (osnovna tabela — sve ostalo se na nju kaci)
--    Napomena: ako tabela vec postoji iz starog projekta, ALTER ce samo
--    dodati kolone koje fale. Postojeci podaci se NE diraju.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destinacije` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `naziv`              VARCHAR(120) NOT NULL,
    `opis`               TEXT         DEFAULT NULL,
    `zemlja`             VARCHAR(60)  DEFAULT NULL,
    `region`             VARCHAR(80)  DEFAULT NULL,
    `sezona`             ENUM('zima','leto') NOT NULL DEFAULT 'zima',
    `lat`                DECIMAL(10,6) DEFAULT NULL,
    `lng`                DECIMAL(10,6) DEFAULT NULL,
    `granicni_prelaz_id` INT          DEFAULT NULL,
    KEY `idx_sezona` (`sezona`),
    CONSTRAINT `fk_dest_prelaz`
        FOREIGN KEY (`granicni_prelaz_id`) REFERENCES `granicni_prelazi`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ako tabela postoji od ranije, dodaj nove kolone (idempotentno):
ALTER TABLE `destinacije`
    ADD COLUMN IF NOT EXISTS `zemlja`             VARCHAR(60)   DEFAULT NULL AFTER `naziv`,
    ADD COLUMN IF NOT EXISTS `region`             VARCHAR(80)   DEFAULT NULL AFTER `zemlja`,
    ADD COLUMN IF NOT EXISTS `sezona`             ENUM('zima','leto') NOT NULL DEFAULT 'zima',
    ADD COLUMN IF NOT EXISTS `lat`                DECIMAL(10,6) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `lng`                DECIMAL(10,6) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `granicni_prelaz_id` INT           DEFAULT NULL;

-- ----------------------------------------------------------------------------
-- 3) DESTINACIJE_SLIKE  (hero, mapa_staza, gallery)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `destinacije_slike`;
CREATE TABLE `destinacije_slike` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id` INT NOT NULL,
    `tip`            ENUM('hero','mapa_staza','gallery') NOT NULL DEFAULT 'gallery',
    `url`            VARCHAR(255) NOT NULL,
    `alt`            VARCHAR(200) DEFAULT NULL,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest_tip` (`destinacija_id`, `tip`),
    CONSTRAINT `fk_slike_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4) STAZE_PUTANJE  (srce sablona!)
--    `tip_klasa` se direktno koristi kao CSS klasa na <path>-u, npr. 'plava'.
--    CSS sam reaguje (.staza.plava { stroke: ... }) i daje sjaj na hover.
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `staze_putanje`;
CREATE TABLE `staze_putanje` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id` INT NOT NULL,
    `tip_klasa`      VARCHAR(40)  NOT NULL,
    `naziv`          VARCHAR(100) DEFAULT NULL,
    `svg_d_putanja`  TEXT         NOT NULL,
    `duzina_km`      DECIMAL(5,1) NOT NULL DEFAULT 0,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest_tip` (`destinacija_id`, `tip_klasa`),
    CONSTRAINT `fk_putanje_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5) TRANSPORT_OPCIJE
--    `stavke_json` cuva niz {label, vrednost} parova — fleksibilan format
--    koji isto radi i za bus, i za avion, i za auto, i za leto (npr. trajekt).
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `transport_opcije`;
CREATE TABLE `transport_opcije` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id` INT NOT NULL,
    `tip`            VARCHAR(40)  NOT NULL DEFAULT 'bus',
    `naziv`          VARCHAR(100) NOT NULL,
    `podnaslov`      VARCHAR(120) DEFAULT NULL,
    `ikona`          VARCHAR(10)  DEFAULT '🚌',
    `stavke_json`    JSON         DEFAULT NULL,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_transport_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6) OPREMA_PAKETI
--    `includes_json` = niz stringova ("Skije", "Kaciga", ...).
--    Za letovanje moze da bude "Ronilacka boca", "Snorkel", "Maska" itd.
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `oprema_paketi`;
CREATE TABLE `oprema_paketi` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id` INT NOT NULL,
    `naziv`          VARCHAR(100) NOT NULL,
    `opis`           TEXT         DEFAULT NULL,
    `cena_eur`       DECIMAL(7,2) NOT NULL,
    `includes_json`  JSON         DEFAULT NULL,
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_oprema_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7) SKOLA_PAKETI
--    Univerzalno: skola skijanja, skola surfanja, skola ronjenja...
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `skola_paketi`;
CREATE TABLE `skola_paketi` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `destinacija_id` INT NOT NULL,
    `naziv`          VARCHAR(100) NOT NULL,
    `opis`           VARCHAR(255) DEFAULT NULL,
    `cena_eur`       DECIMAL(7,2) NOT NULL,
    `jedinica`       VARCHAR(20)  NOT NULL DEFAULT 'osobi',
    `redosled`       SMALLINT     NOT NULL DEFAULT 0,
    KEY `idx_dest` (`destinacija_id`),
    CONSTRAINT `fk_skola_destinacija`
        FOREIGN KEY (`destinacija_id`) REFERENCES `destinacije`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- (Opciono) Brisanje starih, sada nekoriscenih tabela. Otkomentarisi po zelji.
-- ============================================================================
-- DROP TABLE IF EXISTS `ticker_items`;
-- DROP TABLE IF EXISTS `recenzije`;
-- DROP TABLE IF EXISTS `faq`;
-- DROP TABLE IF EXISTS `ski_pas_cene`;
-- DROP TABLE IF EXISTS `vreme_trenutno`;
-- DROP TABLE IF EXISTS `vreme_prognoza`;
-- DROP TABLE IF EXISTS `staze_status`;
-- DROP TABLE IF EXISTS `ski_info`;
-- DROP TABLE IF EXISTS `smestaj`;


-- ============================================================================
-- SEED PODACI — primer destinacije "Les Orres" sa id = 1
-- ============================================================================

-- Granicni prelazi
INSERT INTO `granicni_prelazi` (`naziv`, `iz_drzave`, `u_drzavu`, `tipicno_cekanje_min`) VALUES
('Horgoš',     'Srbija', 'Mađarska',  30),
('Batrovci',   'Srbija', 'Hrvatska',  25),
('Šid',        'Srbija', 'Hrvatska',  15),
('Vrška Čuka', 'Srbija', 'Bugarska',  20);

-- Ako destinacija id=1 ne postoji, dodaj je. Inace dopuni nove kolone.
INSERT INTO `destinacije` (`id`, `naziv`, `opis`, `zemlja`, `region`, `sezona`, `lat`, `lng`, `granicni_prelaz_id`)
VALUES (1, 'Les Orres',
        'Skrivena perla francuskih Alpa — staze pod stalnim suncem, kompaktno selo i savršena dnevna preglednost.',
        'Francuska', 'Francuske Alpe', 'zima', 44.4553, 6.5372, 1)
ON DUPLICATE KEY UPDATE
    `zemlja` = COALESCE(`destinacije`.`zemlja`, VALUES(`zemlja`)),
    `region` = COALESCE(`destinacije`.`region`, VALUES(`region`)),
    `sezona` = VALUES(`sezona`),
    `lat`    = COALESCE(`destinacije`.`lat`,    VALUES(`lat`)),
    `lng`    = COALESCE(`destinacije`.`lng`,    VALUES(`lng`)),
    `granicni_prelaz_id` = COALESCE(`destinacije`.`granicni_prelaz_id`, VALUES(`granicni_prelaz_id`));

-- Demo letnja destinacija — Krit, Grcka
INSERT INTO `destinacije` (`id`, `naziv`, `opis`, `zemlja`, `region`, `sezona`, `lat`, `lng`)
VALUES (2, 'Krit',
        'Najveće grčko ostrvo — kristalno plave plaže, planinski zaledja Lefka Ori i pet hiljada godina Minojske civilizacije na svakom koraku.',
        'Grčka', 'Egejsko more', 'leto', 35.2401, 24.8093)
ON DUPLICATE KEY UPDATE
    `opis`   = VALUES(`opis`),
    `sezona` = VALUES(`sezona`),
    `lat`    = VALUES(`lat`),
    `lng`    = VALUES(`lng`);

-- Slika mape staza (preko koje se crtaju SVG putanje)
INSERT INTO `destinacije_slike` (`destinacija_id`, `tip`, `url`, `alt`, `redosled`) VALUES
(1, 'mapa_staza', 'Slike/les_orres_mapa.jpg', 'Mapa staza Les Orres', 1);

-- SVG putanje staza
-- `tip_klasa` ide direktno u HTML kao CSS klasa. CSS prepoznaje i boji.
INSERT INTO `staze_putanje` (`destinacija_id`, `tip_klasa`, `naziv`, `svg_d_putanja`, `duzina_km`, `redosled`) VALUES
(1, 'plava',  'Plava staza — La Cascade',     'M 200 150 Q 300 220 400 300',  8.5, 1),
(1, 'crvena', 'Crvena staza — Rouge Mélèzes', 'M306.5 123.5C295.3 123.9 292.167 118.667 292 116C295.5 110.5 294.5 111 289.5 103.5C297.1 100.7 303.5 97 305.5 90.5C330.3 92.5 330 90.5 330 84C340.5 74.5 341 74.5 333.5 68.5C335.9 60.1 327.833 58 324 58C319.5 58 317.403 63.6563 312 66.5C302.5 71.5 299.5 74 290.5 76C289.5 79 281.6 84.2 276 85C274 86.6667 268 92.5 268.5 101C265.5 104.5 262 104.7 256 107.5', 12.3, 2),
(1, 'crna',   'Crna staza — Pylône',          'M 320 80 L 310 180 L 290 290', 4.7, 3);

-- Transport opcije (3 razlicita tipa, isti format)
-- Ikone se renderuju kao inline SVG na frontu — `ikona` kolona cuva samo
-- naziv tipa ('bus'/'avion'/'auto') za buducu fleksibilnost.
INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `ikona`, `stavke_json`, `redosled`) VALUES
(1, 'bus', 'Agencijski Autobus', 'Direktna linija', 'bus',
    JSON_ARRAY(
        JSON_OBJECT('label', 'Polazak',      'vrednost', 'Sava Centar, 22:00h'),
        JSON_OBJECT('label', 'Trajanje',     'vrednost', '~20h voznje'),
        JSON_OBJECT('label', 'Povratak',     'vrednost', 'Nedeljom, 14:00h'),
        JSON_OBJECT('label', 'Prtljag',      'vrednost', 'Kofer + ski torba'),
        JSON_OBJECT('label', 'Cena prevoza', 'vrednost', '95 EUR / osobi')
    ), 10),
(1, 'avion', 'Avion + Transfer', 'Najbrza opcija', 'avion',
    JSON_ARRAY(
        JSON_OBJECT('label', 'Aerodrom',           'vrednost', 'BEG - Lyon / Marseille'),
        JSON_OBJECT('label', 'Let',                'vrednost', '~2h 30min'),
        JSON_OBJECT('label', 'Transfer',           'vrednost', 'Aerodrom - Hotel'),
        JSON_OBJECT('label', 'Trajanje transfera', 'vrednost', '~3h'),
        JSON_OBJECT('label', 'Satl cena',          'vrednost', '55 EUR / osobi')
    ), 20),
(1, 'auto', 'Sopstveni Auto', '1580 km od Beograda', 'auto',
    JSON_ARRAY(
        JSON_OBJECT('label', 'Putarina',        'vrednost', '110 EUR povratno'),
        JSON_OBJECT('label', 'Zimska oprema',   'vrednost', 'Obavezna'),
        JSON_OBJECT('label', 'Granicni prelaz', 'vrednost', 'Horgos')
    ), 30);

-- Oprema paketi
INSERT INTO `oprema_paketi` (`destinacija_id`, `naziv`, `opis`, `cena_eur`, `includes_json`, `redosled`) VALUES
(1, 'Starter Komplet',
    'Idealno za početnike i rekreativce. Proverena oprema renomirane klase.',
    22,
    JSON_ARRAY('Skije (all-mountain, početni nivo)', 'Pancerice (toplinski podstavljene)', 'Štapovi + kaiš za zapešće', 'Kaciga (EN 1077 certifikat)'),
    10),
(1, 'Expert Performance',
    'Napredni modeli skija za iskusne skijaše koji traže preciznost i kontrolu na svakom terenu.',
    38,
    JSON_ARRAY('Race/Freeride skije (napredni modeli)', 'Pancerice (race-fit, carbon vložak)', 'Štapovi od karbona', 'Kaciga + zaštitne naočare', 'Zaštitni šorts i back protektor'),
    20);

-- Skola paketi
INSERT INTO `skola_paketi` (`destinacija_id`, `naziv`, `opis`, `cena_eur`, `jedinica`, `redosled`) VALUES
(1, 'Grupni čas (do 6 osoba)', '2h · Svi nivoi · Srpski / Engleski', 18, 'osobi', 10),
(1, 'Individualni čas',        '2h · Personalizovani program',       65, 'čas',   20),
(1, '5-dnevni grupni kurs',    '2h dnevno · Sve uzraste · Sertifikat', 72, 'osobi', 30),
(1, 'Snowboard starter',       '3h · Početnici · Oprema uključena',    48, 'osobi', 40);

-- ============================================================================
-- KRIT (id=2) — Letnja destinacija
--   Iste tabele, isti format, samo drugi sadrzaj.
--   `tip_klasa` na putanjama: 'morska-ruta', 'pesacka-laka', 'pesacka-zahtevna'
--   CSS prepoznaje ove klase i automatski boji (dodaj nove blokove u stylu).
-- ============================================================================

INSERT INTO `destinacije_slike` (`destinacija_id`, `tip`, `url`, `alt`, `redosled`) VALUES
(2, 'mapa_staza', 'https://images.unsplash.com/photo-1601581875309-fafbf2d3ed3a?q=80&w=1600&auto=format&fit=crop', 'Krit mapa', 1),
(2, 'hero',       'https://images.unsplash.com/photo-1601581875309-fafbf2d3ed3a?q=80&w=1600&auto=format&fit=crop', 'Krit obala', 1),
(2, 'gallery',    'https://images.unsplash.com/photo-1601581875309-fafbf2d3ed3a?q=80&w=1200&auto=format&fit=crop', 'Krit plaze',    1),
(2, 'gallery',    'https://images.unsplash.com/photo-1533105079780-92b9be482077?q=80&w=1200&auto=format&fit=crop', 'Krit zalazak',  2),
(2, 'gallery',    'https://images.unsplash.com/photo-1503152394-c571994fd383?q=80&w=1200&auto=format&fit=crop',   'Lefka Ori',     3);

INSERT INTO `staze_putanje` (`destinacija_id`, `tip_klasa`, `naziv`, `svg_d_putanja`, `duzina_km`, `redosled`) VALUES
(2, 'morska-ruta',     'Morska ruta — Balos laguna',  'M 80 200 Q 200 120 380 180 Q 500 230 580 200', 22.0, 1),
(2, 'pesacka-laka',    'Pesacka — Samaria klanac',    'M 150 80 L 200 160 Q 260 200 320 250',          11.5, 2),
(2, 'pesacka-zahtevna','Pesacka — Lefka Ori vrh',     'M 320 80 Q 380 130 420 220 L 460 290',           7.8, 3);

INSERT INTO `transport_opcije` (`destinacija_id`, `tip`, `naziv`, `podnaslov`, `ikona`, `stavke_json`, `redosled`) VALUES
(2, 'avion', 'Direktan let', 'Najbrza opcija', 'avion',
    JSON_ARRAY(
        JSON_OBJECT('label', 'Aerodrom',       'vrednost', 'BEG - Heraklion'),
        JSON_OBJECT('label', 'Let',            'vrednost', '~2h 15min'),
        JSON_OBJECT('label', 'Transfer hotel', 'vrednost', '~40 min'),
        JSON_OBJECT('label', 'Frekvencija',    'vrednost', 'Sezonski, 4x nedeljno'),
        JSON_OBJECT('label', 'Cena karte',     'vrednost', 'od 220 EUR / osobi')
    ), 10),
(2, 'bus', 'Bus + trajekt', 'Pavle / Patras / Heraklion', 'bus',
    JSON_ARRAY(
        JSON_OBJECT('label', 'Polazak',  'vrednost', 'Sava Centar'),
        JSON_OBJECT('label', 'Trajanje', 'vrednost', '~30h sa trajektom'),
        JSON_OBJECT('label', 'Trajekt',  'vrednost', 'Patras - Heraklion'),
        JSON_OBJECT('label', 'Cena',     'vrednost', '180 EUR / osobi')
    ), 20),
(2, 'auto', 'Auto + trajekt', 'Najfleksibilnija opcija', 'auto',
    JSON_ARRAY(
        JSON_OBJECT('label', 'Granicni prelaz', 'vrednost', 'Preševo'),
        JSON_OBJECT('label', 'Distanca',        'vrednost', '~2200 km do Patrasa'),
        JSON_OBJECT('label', 'Trajekt',         'vrednost', 'Patras - Heraklion (~9h)'),
        JSON_OBJECT('label', 'Putarina',        'vrednost', '85 EUR jedan smer')
    ), 30);

INSERT INTO `oprema_paketi` (`destinacija_id`, `naziv`, `opis`, `cena_eur`, `includes_json`, `redosled`) VALUES
(2, 'Snorkel paket',
    'Osnovni komplet za istrazivanje plicaka i koralnih grebena.',
    12,
    JSON_ARRAY('Maska sa silikonskom oblogom', 'Disaljka', 'Peraja', 'Vodootporna torba'),
    10),
(2, 'Ronilacki paket',
    'Pun komplet za sertifikovane ronioce — Padi licenca obavezna.',
    45,
    JSON_ARRAY('Boce (2x12L)', 'Regulator', 'Kompenzator plovnosti', 'Wetsuit 3mm', 'Kompjuter za ronjenje'),
    20);

INSERT INTO `skola_paketi` (`destinacija_id`, `naziv`, `opis`, `cena_eur`, `jedinica`, `redosled`) VALUES
(2, 'Pocetni kurs ronjenja',   '3 dana · Padi Open Water Diver', 320, 'osobi', 10),
(2, 'Skola kajtanja',           '4h · Pocetni nivo, oprema ukljucena', 95, 'osobi', 20),
(2, 'Privatni cas jedrenja',    '3h · Mali brod sa kapetanom',         140, 'cas',   30),
(2, 'Vodjeni snorkel obilazak', '2h · Grupa do 8 osoba',                28, 'osobi', 40);

-- ============================================================================
-- KRAJ — pokreni u phpMyAdmin nad bazom `peak_palm`
-- ============================================================================
