<?php
/**
 * Fichier de configuration du module bfw-sql
 * @author Vermeulen Maxime <bulton.fr@gmail.com>
 * @package bfw-sql
 * @version 1.0
 */

//*** Base De Données ***
$bd_host     = ''; //Le serveur où est la base (ex: localhost)
$bd_name     = ''; //Nom de la Base de donnée
$bd_user     = ''; //Le nom d'utilisateur
$bd_pass     = ''; //Le mot de passe
$bd_type     = ''; //Le type de bdd (ex: mysql) (ex: pgsql)
$bd_prefix   = ''; //Préfix en début de table
$bd_observer = false; //Active ou non l'observeur retournant le EXPLAIN sur la requête. (erreur si requête préparé)
$bd_enabled  = false; //Active ou non la connexion sql
//*** Base De Données *** 
