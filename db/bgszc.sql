CREATE DATABASE IF NOT EXISTS lol_bgszc;

USE lol_bgszc;

CREATE TABLE iskola (
    iskola_id INT PRIMARY KEY AUTO_INCREMENT,
    nev VARCHAR(255) NOT NULL,
    cim VARCHAR(255) NOT NULL
);

CREATE TABLE osztalyfonok (
    osztalyfonok_id INT PRIMARY KEY AUTO_INCREMENT,
    nev VARCHAR(255) NOT NULL,
    email VARCHAR(130) NOT NULL,
    telefon VARCHAR(20) NOT NULL
);

CREATE TABLE diak (
    diak_id INT PRIMARY KEY AUTO_INCREMENT,
    felhasznalonev VARCHAR(255) UNIQUE NOT NULL,
    jelszo VARCHAR(255) NOT NULL, -- Titkosítva!
    nev VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    osztalyfonok_id INT,
    iskola_id INT,
    ossz_ledolgozott_orak INT DEFAULT 0,
    FOREIGN KEY (osztalyfonok_id) REFERENCES osztalyfonok(osztalyfonok_id),
    FOREIGN KEY (iskola_id) REFERENCES iskola(iskola_id)
);

CREATE TABLE admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    felhasznalonev VARCHAR(255) UNIQUE NOT NULL,
    jelszo VARCHAR(255) NOT NULL  -- Titkosítva!
);

CREATE TABLE esemeny (
    esemeny_id INT PRIMARY KEY AUTO_INCREMENT,
    datum DATE NOT NULL,
    helyszin VARCHAR(255) NOT NULL,
    terheltseg ENUM('alacsony', 'magas') NOT NULL
);

CREATE TABLE foglalkozas (
    foglalkozas_id INT PRIMARY KEY AUTO_INCREMENT,
    nev VARCHAR(130) NOT NULL,
    leiras VARCHAR(255)
);

CREATE TABLE esemeny_foglalkozas (
    esemeny_foglalkozas_id INT PRIMARY KEY AUTO_INCREMENT,
    esemeny_id INT,
    foglalkozas_id INT,
    teljesitheto_orak_szama INT NOT NULL,
    szukseges_mentor_szam INT NOT NULL,
    FOREIGN KEY (esemeny_id) REFERENCES esemeny(esemeny_id),
    FOREIGN KEY (foglalkozas_id) REFERENCES foglalkozas(foglalkozas_id)
);

CREATE TABLE mentor_foglalkozasok (
    mentor_foglalkozas_id INT PRIMARY KEY AUTO_INCREMENT,
    diak_id INT,
    foglalkozas_id INT,
    FOREIGN KEY (diak_id) REFERENCES diak(diak_id),
    FOREIGN KEY (foglalkozas_id) REFERENCES foglalkozas(foglalkozas_id)
);

CREATE TABLE rangsor (
    rangsor_id INT PRIMARY KEY AUTO_INCREMENT,
    esemeny_foglalkozas_id INT,
    diak_id INT,
    rangsor_szam INT NOT NULL,
    FOREIGN KEY (esemeny_foglalkozas_id) REFERENCES esemeny_foglalkozas(esemeny_foglalkozas_id),
    FOREIGN KEY (diak_id) REFERENCES diak(diak_id)
);

CREATE TABLE reszvetelnaplo (
    reszvetel_id INT PRIMARY KEY AUTO_INCREMENT,
    diak_id INT,
    esemeny_foglalkozas_id INT,
    megjegyzes TEXT,
    FOREIGN KEY (diak_id) REFERENCES diak(diak_id),
    FOREIGN KEY (esemeny_foglalkozas_id) REFERENCES esemeny_foglalkozas(esemeny_foglalkozas_id)
);