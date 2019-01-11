<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once "merossCmd.class.php";

class meross extends eqLogic
{

    private static $_pathJson = __DIR__ . '/../../3rparty/result.json';
    private static $_pathScript = __DIR__ . '/../../3rdparty/meross.sh';


    public static $_widgetPossibility = array(
        'custom' => true,
        'custom::layout' => false,
        'parameters' => array(
            'sub-background-color' => array(
                'name' => 'Couleur de la barre de contrôle',
                'type' => 'color',
                'default' => 'rgba(0,0,0,0.5)',
                'allow_transparent' => true,
                'allow_displayType' => true,
            ),
        ),
    );

    public static function cron()
    {
        self::launchScript();
        log::add('meross', 'debug', '=== MAJ DES INFOS ===');
        foreach (eqLogic::byType('meross', true) as $eqLogic) {
            $eqLogic->updateInfo();
        }
    }

    public function postSave()
    {

    }

    public function launchScript()
    {
        try {
            shell_exec("sudo sh " . self::$_pathScript);
        } catch (\Exception $e) {
            log::add('meross', 'error', 'pas de fichier script trouvé ' . $e);
        }
    }

    public function getJson()
    {
        try {
            $data = file_get_contents(self::$_pathJson);
            $json = json_decode($data, true);
            return $json;
        } catch (\Exception $e) {
            log::add('meross', 'error', 'pas de fichier json trouvé ' . $e);
        }
    }
    public function syncMeross()
    {
        log::add('meross', 'debug', '=== AJOUT DES EQUIPEMENTS ===');

        $json = self::getJson();
        foreach ($json as $key=>$devices) {
            $device = self::byLogicalId($key, 'meross');
            if (!is_object($device)) {
                log::add('meross', 'debug','Ajout de l\'équipement ' . $devices["name"]);
                $device = new self();
                $device->setName($devices["name"]);
                $device->setEqType_name("meross");
                $device->setLogicalId($key);
                $device->setConfiguration('type', $devices["type"]);
                $device->setConfiguration('ip', $devices["ip"]);
                $device->setConfiguration('mac', $devices["mac"]);
                $device->setConfiguration('online', $devices["online"]);
                $device->save();
            } else {
                log::add('meross', 'debug','équipement' . $devices["name"] . ' deja ajouter ');
            }

            $cmd = $device->getCmd(null, "consommation");

            if (!is_object($cmd)) {
                log::add('meross', 'debug','-- ajout de la commande consommation ');
                $cmd = new merossCmd();
                $cmd->setLogicalId("consommation");
                $cmd->setName(__("consommation", __FILE__));
                $cmd->setType("info");
                $cmd->setSubType("numeric");
                $cmd->setEqLogic_id($device->getId());
                $cmd->save();
            }


            $cmd = $device->getCmd(null, "status");
            if (!is_object($cmd)) {
                log::add('meross', 'debug','-- ajout de la commande state ');

                $cmd = new merossCmd();
                $cmd->setLogicalId("status");
                $cmd->setName(__("status", __FILE__));
                $cmd->setType("info");
                $cmd->setSubType("binary");
                $cmd->setEqLogic_id($device->getId());
                $cmd->save();
            }



            $cmd = $device->getCmd(null, "on");
            if (!is_object($cmd)) {
                log::add('meross', 'debug','-- ajout des commandes on et off ');
                $cmd = new merossCmd();
                $cmd->setLogicalId("on");
                $cmd->setName(__("on", __FILE__));
                $cmd->setType("action");
                $cmd->setSubType("other");
                $cmd->setEqLogic_id($device->getId());
                $cmd->save();
            }
            $cmd = $device->getCmd(null, "off");
            if (!is_object($cmd)) {
                $cmd = new merossCmd();
                $cmd->setLogicalId("off");
                $cmd->setName(__("off", __FILE__));
                $cmd->setType("action");
                $cmd->setSubType("other");
                $cmd->setEqLogic_id($device->getId());
                $cmd->save();
            }


        }
    }

    public function updateInfo()
    {
        try {
            $infos = self::getJson();
        } catch (\Exception $e) {
        }

        foreach ($infos as $key=>$devices) {
            if ($key == $this->getLogicalId()) {
                log::add('meross', 'debug', 'infos de : ' . $devices['name']);
                if (isset($devices['status'])) {
                    log::add('meross', 'debug', 'etat: ' . $devices['status']);
                    $this->checkAndUpdateCmd('status', $devices['status']);
                }
                if (isset($devices['consumption'])) {
                    $this->checkAndUpdateCmd('consommation', $devices['consumption']);
                }

                if (isset($devices['online'])) {
                    $this->setConfiguration('online', $devices['online']);
                }

            }
        }
    }

}
