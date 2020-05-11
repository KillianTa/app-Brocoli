<?php
	require_once("../php/fonGenerate/initFonction.php");
	require_once("../php/fonGenerate/checkFonction.php");

	if(!isset($_SESSION)) session_start();

	

	#On regarde qui nous envoie des données
	#Si c'est replay.php, on effectue une requete sql pour récupérer un modèle déjà sauvegarder
	if (isset($_SESSION['libelle'])) {
		$modeleGen = array();
		$modeleGen = fillFromReplay($_SESSION['libelle']);
		
	} 
	#Si c'es index.php, on rempli juste le tableau
	elseif (isset($_SESSION['nomModele'])) {
		$modeleGen = array();
		$modeleGen = fillFromIndex();
	
	}

	#On vide $_SESSION pour éviter d'avoir des erreurs
	//$_SESSION = array();
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
								<input type="text" class="form-control" name="nomModele" placeholder="Nom du modèle" value=<?php if (isset($modeleGen)) echo $modeleGen['nomModele']; ?>>
							</div>
						</div>
						<div class="form-group row">
							<label for="nbLigne" class="col-sm-2 col-form-label">Nombre de Ligne:</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" name="nbLigne" placeholder="Nombre de ligne" value=<?php if (isset($modeleGen)) echo $modeleGen['nbLigne']; ?>>
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
									echo '<td><input type="text" class="form-control" value ='.$nomChamp.'></td>';	#Génère le nom
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
				<section class="container" id="tabBord">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" id="save">
						<label class="form-check-label" for="save">Sauvegarder</label>
					</div>
					<hr>
					<div class="form-row align-items-center">		
						<div class="col-auto">
							<input type="text" id="nomFichier" class="form-control" placeholder="nomFichier" value= <?php if (isset($modeleGen)) echo $modeleGen['nomModele']; ?>>
						</div>
						<div class="col-auto">
							<select id="typeFichier" class="form-control">
								<option selected>.sql</option>
								<option>.csv</option>
							</select>
						</div>		
					</div>
					<hr>
					<div class="form-row align-items-center">	
						<div class="col-auto">
							<button type="submit" class="btn btn-outline-success btn-lg" id="btnGenerer">Générer</button>						
						</div>
						<div class="col-auto">
							<button type="submit" class="btn btn-outline-info btn-lg" id="btnDownload">Télécharger</button>
						</div>
					</div>
				</section>
				<hr>
			</div>
		</div>
	</form>
	<hr>
	<div class="row">
		<div class="col-md-8">
			<div class="card" style=" margin-left: 10rem;">
				<div class="card-body">
					<h5 class="card-title">Console</h5>
					<p class="card-text">
						
					</p>
				</div>
			</div>
		</div>
		<div class="col-md-4" id="console">
			<?php
				
				#On vérifie si chaque position est unique puis on update notre variable modeleGen
				if (checkPosition($modeleGen['nbType'])) {
					for ($i = 1; $i <= $modeleGen['nbType']; $i++) {
						$modeleGen[$i-1]->setId($_POST['pos'.$i]);
					}
				} else {
					echo "Vous avez saisie plusieurs fois la même position pour vos types <br>";
				}

				#Boucle de vérification
				for ($i=0; $i < $modeleGen['nbType'] ; $i++) { 
					var_dump($modeleGen[$i]->getId());
					echo '<br>';
				}
				
				


			?>
		</div>
	</div>
	
	<?php require "footer.html"; ?>


</body>
</html>