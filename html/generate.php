<?php
	require_once("../php/fonGenerate/initFonction.php");
	require_once("../php/fonGenerate/checkAndSaveFonction.php");
	require_once("../php/fonGenerate/genAndDownFonction.php");
	if(!isset($_SESSION)) session_start();

	/**** INITIALISATION DE LA PAGE ****/

	#On regarde qui nous envoie des données
	#Si c'est replay.php, on effectue une requete sql pour récupérer un modèle déjà sauvegardé
	if (isset($_SESSION['libelle'])) {
		$modeleGen = array();
		$modeleGen = fillFromReplay($_SESSION['libelle']);
	} 

	#Si c'est index.php, on rempli juste le tableau
	elseif (isset($_SESSION['nomModele'])) {
		$modeleGen = array();
		$modeleGen = fillFromIndex();
	}

	/**** VERIFICATION DES DONNEES ****/

	$console = "";
	if (isset($_POST['btnGenerer']) || isset($_POST['btnDownload'])) {
		$isHere = true;
		#On parcours chaque type
		for ($i = 0; $i < $modeleGen['nbType']; $i++) {

			#On vérifie la validité du nom et on le sauvegarde
			$nomVerifier = $modeleGen[$i]->verifNom($_POST[$modeleGen[$i]->getId().'nom']);
			if ($nomVerifier) {
				$isHere = false;
				$console .= $nomVerifier;
			} else {
				$modeleGen[$i]->setNomChamp(htmlspecialchars($_POST[$modeleGen[$i]->getId().'nom']));
			}

			#Vérification des Booleans
			if ($modeleGen[$i]->getTypeChamp() == "Boolean") {
				$valueVerifier = $modeleGen[$i]->verifValue($_POST[$modeleGen[$i]->getId()]);
			} 
			#Vérification des autres types champs
			else {
				$valueVerifier = $modeleGen[$i]->verifValue($_POST[$modeleGen[$i]->getId()."1"], $_POST[$modeleGen[$i]->getId()."2"]);
			}

			#En fonction de l'état de retour on affiche une erreur sinon on affecte la valeur
			if ($valueVerifier) {
				$isHere = false;
				$console .= $valueVerifier;
			} else {
				if ($modeleGen[$i]->getTypeChamp() == "Boolean") {
					$modeleGen[$i]->setEtat(htmlspecialchars($_POST[$modeleGen[$i]->getId()]));

				} elseif ($modeleGen[$i]->getTypeChamp() == "Char" || $modeleGen[$i]->getTypeChamp() == "Varchar") {
					$modeleGen[$i]->setLongueur(htmlspecialchars($_POST[$modeleGen[$i]->getId()."1"]));	
					$modeleGen[$i]->setFichier(htmlspecialchars($_POST[$modeleGen[$i]->getId()."2"]));

				} elseif ($modeleGen[$i]->getTypeChamp() == "DateTimes") {
					$dateMin = explode("_", $_POST[$modeleGen[$i]->getId()."1"]);
					$dateMax = explode("_", $_POST[$modeleGen[$i]->getId()."2"]);
					$modeleGen[$i]->setValMin($dateMin[0]."_".$dateMin[1]);
					$modeleGen[$i]->setValMax($dateMax[0]."_".$dateMax[1]);

				}else {
					$modeleGen[$i]->setValMin(htmlspecialchars($_POST[$modeleGen[$i]->getId()."1"]));
					$modeleGen[$i]->setValMax(htmlspecialchars($_POST[$modeleGen[$i]->getId()."2"]));
				}	
			}
		}
		#On récupère le nouveau nom du modèle, le nom du téléchargement et le nom de la table
		if (isset($_POST['nomModele']) && isset($_POST['nomFichier']) && isset($_POST['nomTable'])) {
			$modeleGen['nomModele'] = htmlspecialchars($_POST['nomModele']);
			$modeleGen['nomFichier'] = htmlspecialchars($_POST['nomFichier']);
			$modeleGen['nomTable'] = htmlspecialchars($_POST['nomTable']);
		} else {
			$isHere = false;
			$console .= "Veuillez saisir un nom de fichier, un nom de modèle et un nom de table. <br>";
		}

		#On vérifie si le nombre de ligne est supérieur à 0 
		if ((int)$_POST['nbLigne'] > 0) {
			$modeleGen['nbLigne'] = htmlspecialchars($_POST['nbLigne']);
		} else {
			$isHere = false;
			$console .= "Votre nombre de ligne doit être supérieur à 0. <br>";
		}

		#On vérifie si chaque position est unique puis on update notre variable modeleGen
		if (checkPosition($modeleGen['nbType'])) {
			for ($i = 1; $i <= $modeleGen['nbType']; $i++) {
				$modeleGen[$i-1]->setId($_POST['pos'.$i]);
			}
		} else {
			$isHere = false;
			$console .= "Vous avez saisi plusieurs fois la même position pour vos types. <br>";
		}

		#Si on a validé toutes les conditions précédentes on regarde ce que l'utilisateur veut faire
		if ($isHere) {
			#S'il a coché la case sauvegardé, on sauvegarde le modèle en cours 
			if (isset($_POST['save'])) {
				$console .= saveModele($modeleGen);
			}

			#Si le bouton généré est activé
			if (isset($_POST['btnGenerer'])) {
				$modeleGen['pathFichier'] = generateData($modeleGen, $_POST['typeFichier']);
				$_SESSION['pathFichier'] = $modeleGen['pathFichier'];
				$console .= "Votre jeu de donnée à bien été généré et est prêt à être téléchargé. <br>";
			}	

			#Si le bouton téléchargé est activé
			if (isset($_POST['btnDownload'])) {
				#On vérifie si le fichier  a été crée
				if ($modeleGen['pathFichier']) {
					header('Content-disposition: attachment; filename="'.$modeleGen['pathFichier'].'"');
					header('Content-Type: application/force-download');
					header('Content-Transfer-Encoding: fichier');
					header('Content-Length: '.filesize($modeleGen['pathFichier']));
					header('Pragma: no-cache');
					header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
					header('Expires: 0');
					readfile($modeleGen['pathFichier']);
					exit();

				} else {
					$console .= "Veuillez généner d'abord le fichier avant de le télécharger. <br>";
				}
			}
		}
	}

?>

<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title>Projet Broccoli</title>
		<link rel="stylesheet" type="text/css" href="../css/index.css">
	</head>
	<body>
		<?php require "header.html"; ?>

		<form method="POST">
			<div class="row">
				<div class="col-md-8">
					<section class="container">
						<table class="table">
							<div class="form-group row">
								<label for="nomModel" class="col-sm-2 col-form-label">Nom du modèle:</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="nomModele" placeholder="Nom du modèle" 
										  value=<?php if (isset($modeleGen)) echo $modeleGen['nomModele']; ?>>
								</div>
							</div>
							<div class="form-group row">
								<label for="nbLigne" class="col-sm-2 col-form-label">Nombre de ligne:</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="nbLigne" placeholder="Nombre de ligne" 
										   value=<?php if (isset($modeleGen)) echo $modeleGen['nbLigne']; ?>>
								</div>
							</div>
							<div class="form-group row">
								<label for="nbLigne" class="col-sm-2 col-form-label">Nom de la table:</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="nomTable" placeholder="Nom de la table" 
										   value=<?php if (isset($modeleGen)) echo $modeleGen['nomTable']; ?>>
								</div>
							</div>
							<thead>
								<tr>
									<th scope="col">Position</th>
									<th scope="col">Type champ</th>
									<th scope="col">Nom du champ</th>
									<th scope="col">Valeurs</th>
								</tr>
							</thead>
							<tbody>

								<?php
									#On fais une boucle pour parcourir tous les types
									for ($i = 0; $i < $modeleGen['nbType']; $i++) {
										#On récupère les valeurs nécessaires et si elles sont nulles on ne met rien 
										if ($modeleGen[$i]->getNomChamp() != NULL) {
											$nomChamp = $modeleGen[$i]->getNomChamp();
										} else {
											$nomChamp = " ";
										}

										echo '<tr>';
										echo positionType($modeleGen['nbType'], $modeleGen[$i]->getId(), $i+1);			#Génère la position
										echo '<th scope="row">'.$modeleGen[$i]->getTypeChamp().'</th>';					#Génère le type
										echo '<td><input type="text" class="form-control" name="'.$modeleGen[$i]->getId().'nom"'.
											 'value ='.$nomChamp.'></td>';												#Génère le nom
										echo switchValue($modeleGen[$i]); 												#Génère les valeurs
										echo '</tr>';
									}
								?>
							</tbody>
						</table>
					</section>
				</div>
				<div class="col-md-4">
					<hr>
					<section class="container">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="save">
							<label class="form-check-label" for="save">Sauvegarder le modèle</label>
						</div>
						<hr>
						<div class="form-row align-items-center">		
							<div class="col-auto">
								<input type="text" name="nomFichier" class="form-control" placeholder="nomFichier" 
										value= <?php if (isset($modeleGen)) echo $modeleGen['nomFichier']; ?>>
							</div>
							<div class="col-auto">
								<select name="typeFichier" class="form-control">
									<option selected>.sql</option>
									<option>.csv</option>
								</select>
							</div>		
						</div>
						<hr>
						<div class="form-row align-items-center">	
							<div class="col-auto">
								<button type="submit" class="btn btn-outline-success btn-lg" name="btnGenerer">Générer</button>						
							</div>
							<div class="col-auto">
								<button type="submit" class="btn btn-outline-info btn-lg" name="btnDownload">Télécharger</button>
							</div>
						</div>
					</section>
					<hr>
				</div>
			</div>
		</form>
		<hr>
		<div class="row">
			<div class="col-md-6">
				<div class="card" style=" margin-left: 10rem;">
					<div class="card-body">
						<h5 class="card-title">Console</h5>
						<p class="card-text">
							<?php
								#Si le fichier existe on affiche un exemple du fichier qui a été généré
								$exemple = checkAndDisplay($modeleGen['pathFichier']);
								if ($exemple) {
									foreach ($exemple as $fields) {
										echo $fields."<br>";
									}
								}	
							 ?>
						</p>
					</div>
				</div>
			</div>
			<div class="col-md-6" id="console">
				<?php echo $console; ?>
			</div>
		</div>
		<?php require "footer.html"; ?>
	</body>
</html>