<?php

/*
 * This file is part of the NextDom software (https://github.com/NextDom or http://nextdom.github.io).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 2.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Fichier appelé lorsque le plugin effectue une requête Ajax
 */

try {
    // Ajoute le fichier du core qui se charge d'inclure tous les fichiers nécessaires
    require_once __DIR__ . '/../../../../core/php/core.inc.php';

    // Ajoute le fichier de gestion des authentifications
    include_file('core', 'authentification', 'php');

    // Test si l'utilisateur est connecté
    if (!isConnect('admin')) {
        // Lève un exception si l'utilisateur n'est pas connecté avec les bons droits
        throw new \Exception(__('401 - Accès non autorisé', __FILE__));
    }

    // Initialise la gestion des requêtes Ajax
    ajax::init();

    
    if (init('action') == 'syncMeross') {
        
        if(config::byKey('merossEmail', 'meross') == 'NextDom4Ever')
        {
            // Create fake device for developpement
            meross::syncMeross(true);
        } else {
            meross::syncMeross(false);
        }
        ajax::success();
    }

    if (init('action') == 'applyDef') {
		$eqLogic = meross::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('Meross unknown eqLogic=', __FILE__) . init('id'));
        }
        // If you want to recreate all cmds
		if (init('deleteCommand') == 1){
			foreach ($eqLogic->getCmd() as $cmd) {
				$cmd->remove();
			}
        }
        meross::applyDef($eqLogic,$eqLogic->getConfiguration('type'),true);
		ajax::success();
	}

    // Lève une exception si la requête n'a pas été traitée avec succès (Appel de la fonction ajax::success());
    throw new \Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (\Exception $e) {
    // Affiche l'exception levé à l'utilisateur
    ajax::error(displayExeption($e), $e->getCode());
}