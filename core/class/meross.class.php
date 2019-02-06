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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once "merossCmd.class.php";

class meross extends eqLogic
{
    private static $_FakeJson = __DIR__ . '/../../3rdparty/fakedevices.json';
    private static $_Script = __DIR__ . '/../../3rdparty/meross.sh';

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

    public static function cron15()
    {
        log::add('meross', 'debug', 'cron15: Update informations for all eqLogics...');
        self::syncMeross(false);
        log::add('meross', 'debug', 'cron15: Cron completed.');
    }

    
    /**
     * Launch sh script to use meross lib
     *
     * @param  mixed $_args
     *
     * @return void
     */
    public static function launchScript($_args)
    {
        $email = config::byKey('merossEmail', 'meross');
        $password = config::byKey('merossPassword', 'meross');
        if ($email == '' || $password == '') {
            log::add('meross', 'error', 'shell_exec: Email or password not provided. Please go to plugin configuration.');
            return null;
        }
        try {
            $command = "sh " . self::$_Script . ' --email ' . $email . ' --password ' . $password . ' ' . $_args;
            $log = str_replace($password, 'xxx', str_replace($email, 'xxx', $command));
            log::add('meross', 'debug', 'shell_exec: ' . $log);
            $stdout = shell_exec($command);
            log::add('meross', 'debug', 'shell_exec: result: ' . $stdout);
            return $stdout;
        } catch (\Exception $e) {
            log::add('meross', 'error', 'shell_exec: Unable to launch script. ' . $e);
        }
        return null;
    }

    
    /**
     * Decode JSON from stdout
     *
     * @param  mixed $_stdout
     *
     * @return void
     */
    public static function getJson($_stdout)
    {
        try {
            $json = json_decode($_stdout, true);
            return $json;
        } catch (\Exception $e) {
            log::add('meross', 'error', 'unable to decode json. ' . $e);
        }
        return null;
    }

    
    /**
     * Decode JSON from file
     *
     * @param  mixed $_file
     *
     * @return void
     */
    public static function getJsonFromFile($_file)
    {
        try {
            $data = file_get_contents($_file);
            $json = json_decode($data, true);
            return $json;
        } catch (\Exception $e) {
            log::add('meross', 'error', 'unable to decode json from ' . $_file . '. ' .  $e);
        }
        return null;
    }

    
    /**
     * Launch synchronization from Meross cloud
     *
     * @param  boolean $_fakeDevices True if you want to load fake devices
     *
     * @return void
     */
    public static function syncMeross($_fakeDevices = false)
    {
        log::add('meross', 'debug', 'syncMeross: Load devices from Meross Cloud');
        if ($_fakeDevices == false) {
            $stdout = self::launchScript('--refresh --show');
            $json = self::getJson($stdout);
        } else {
            log::add('meross', 'debug', 'syncMeross: Load fake devices for developement');
            $json = self::getJsonFromFile(self::$_FakeJson);
            $stdout = json_encode($json);
        }

        foreach ($json as $key=>$devices) {
            $eqLogic = self::byLogicalId($key, 'meross');

            // Test if model is supported
            if (!file_exists(__DIR__ . '/../../core/config/devices/'.$devices["type"].'/def.json')) {
                log::add('meross', 'info', 'syncMeross: Model=' . $devices["type"] . ' not supported');
            } else {
                if (!is_object($eqLogic)) {
                    // Device doesn't exist in DB
                    log::add('meross', 'debug', 'syncMeross: Add device=' . $devices["name"]);
                    $eqLogic = new self();
                    $eqLogic->setName($devices["name"]);
                    $eqLogic->setEqType_name("meross");
                    $eqLogic->setLogicalId($key);
                } else {
                    log::add('meross', 'debug', 'syncMeross: Update device=' . $devices["name"]);
                }

                // Update infos of eqLogic (for both, adding and updating)
                self::updateEqLogicConfig($eqLogic, $devices);

                // Load device def from json
                self::applyDef($eqLogic, $devices["type"]);

                // For Update all cmds values
                log::add('meross', 'debug', 'syncMeross: Update cmds values for eqLogic=' . $eqLogic->getLogicalId());
                self::updateInfo($eqLogic, $stdout);
            }
        }

        log::add('meross', 'debug', 'syncMeross: synchronization completed.');
    }


    /**
     * Load device definition from def.json and apply to cmds
     *
     * @param  mixed $_eqlogic
     * @param  mixed $_type
     * @param  boolean $_force
     *
     * @return void
     */
    public static function applyDef($_eqlogic, $_type, $_force = false)
    {
        log::add('meross', 'debug', 'syncMeross: Load device definition from def.json and apply to cmds for eqLogic=' . $_eqlogic->getLogicalId());
        // Load device def from json
        $jsonCmd = self::getJsonFromFile(__DIR__ . '/../../core/config/devices/' . $_type . '/def.json');
        foreach ($jsonCmd['commands'] as $key=>$commandes) {
            $cmd = $_eqlogic->getCmd(null, $commandes['logicalId']);
            if (!is_object($cmd)) {
                // Add cmd to eqLogic
                log::add('meross', 'debug', 'syncMeross: - Add cmd=' .$commandes['logicalId']);
                $cmd = new merossCmd();
                $cmd->setName(__($commandes['name'], __FILE__));
                $cmd->setIsVisible($commandes['isVisible']);
                // Widget for cmd
                if (isset($commandes['template']['dashboard'])) {
                    $cmd->setTemplate('dashboard', $commandes['template']['dashboard']);
                } else {
                    $cmd->setTemplate('dashboard', 'default');
                }
                if (isset($commandes['template']['mobile'])) {
                    $cmd->setTemplate('mobile', $commandes['template']['mobile']);
                } else {
                    $cmd->setTemplate('mobile', 'default');
                }
                if (isset($commandes['isHistorized']) && $commandes['type'] == 'info') {
                    $cmd->setIsHistorized($commandes['isHistorized']);
                } else {
                    $cmd->setIsHistorized(false);
                }
                $cmd->setLogicalId($commandes['logicalId']);
                $cmd->setEqLogic_id($_eqlogic->getId());
            } else {
                // cmd already exist
                log::add('meross', 'debug', 'syncMeross: - Update cmd=' .$commandes['logicalId']);
            }
    
            // Update cmd def
            $cmd->setType($commandes['type']);
            $cmd->setSubType($commandes['subtype']);
            $cmd->setGeneric_type($commandes['generic_type']);
            $cmd->setOrder($commandes['order']);
            if (isset($commandes['unite']) && $commandes['type'] == 'info') {
                $cmd->setUnite($commandes['unite']);
            }
    
            // If force update cmd from def
            if ($_force) {
                log::add('meross', 'debug', 'syncMeross: - Force update from def');
                $cmd->setName(__($commandes['name'], __FILE__));
                $cmd->setIsVisible($commandes['isVisible']);
                if (isset($commandes['template']['dashboard'])) {
                    $cmd->setTemplate('dashboard', $commandes['template']['dashboard']);
                } else {
                    $cmd->setTemplate('dashboard', 'default');
                }
                if (isset($commandes['template']['mobile'])) {
                    $cmd->setTemplate('mobile', $commandes['template']['mobile']);
                } else {
                    $cmd->setTemplate('mobile', 'default');
                }
                if (isset($commandes['isHistorized']) && $commandes['type'] == 'info') {
                    $cmd->setIsHistorized($commandes['isHistorized']);
                } else {
                    $cmd->setIsHistorized(false);
                }
            }

            // Save to DB
            $cmd->save();
                        
            $splitCommandes = explode("_", $commandes['logicalId']);
            if ($splitCommandes[0] == 'onoff') {
                // Mémorise l'ID de la cmd onoff_x pour affecter aux cmd "on_x" & "off_x"
                $etatid =  $cmd->getId();
            } elseif ($splitCommandes[0] == 'on' || $splitCommandes[0] == 'off') {
                // Affecte l'ID de la cmd onoff_x en value
                log::add('meross', 'debug', 'syncMeross: - Set value : ' .$commandes["value"]);
                $cmd->setValue($etatid);
                $cmd->save();
            }
        }
    }


    public static function updateInfo($_eqLogic, $_stdout)
    {
        log::add('meross', 'debug', 'updateInfo: ' . $_stdout);

        try {
            $infos = self::getJson($_stdout);
        } catch (\Exception $e) {
            log::add('meross', 'error', $e);
            return;
        }

        foreach ($infos as $key=>$devices) {
            if ($key == $_eqLogic->getLogicalId()) {
                foreach ($devices as $key2=>$Commands) {
                    $cmd = $_eqLogic->getCmd(null, $key2);
                    if (!is_array($cmd) || !is_object($cmd)) {
                        if ($key2 == "onoff") {
                            foreach ($Commands as $key3=>$value) {
                                log::add('meross', 'debug', 'updateInfo: -channel_' . $key3 .'=' .  $value);
                                $_eqLogic->checkAndUpdateCmd("onoff_".$key3, $value);
                            }
                        } else {
                            log::add('meross', 'debug', 'updateInfo: -' . $key2 .'=' . json_encode($devices[$key2]));
                            $_eqLogic->checkAndUpdateCmd($key2, $devices[$key2]);
                        }
                    }
                }

                self::updateEqLogicConfig($_eqLogic, $devices);
                break;
            }
        }

        log::add('meross', 'debug', 'updateInfo: Update completed');
    }
    
    /**
     * Update informations of eqLogic and save to configuration
     *
     * @param  mixed $_eqLogic
     * @param  mixed $_device
     *
     * @return void
     */
    public static function updateEqLogicConfig($_eqLogic, $_device)
    {
        log::add('meross', 'debug', 'updateEqLogicConfig: Update eqLogic informations');
        if ($_device["type"] != '') {
            $_eqLogic->setConfiguration('type', $_device["type"]);
        }
        if ($_device['ip'] != '') {
            $_eqLogic->setConfiguration('ip', $_device['ip']);
        }
        if ($_device["mac"] != '') {
            $_eqLogic->setConfiguration('mac', $_device["mac"]);
        }
        if ($_device['firmversion'] != '') {
            $_eqLogic->setConfiguration('firmversion', $_device['firmversion']);
        }
        if ($_device['hardversion'] != '') {
            $_eqLogic->setConfiguration('hardversion', $_device['hardversion']);
        }
        if ($_device['name'] != '') {
            $_eqLogic->setConfiguration('appname', $_device['name']);
        }
        if ($_device['online'] != '') {
            $_eqLogic->setConfiguration('online', $_device['online']);
        } else {
            $_eqLogic->setConfiguration('online', '0');
        }

        $_eqLogic->save();
        log::add('meross', 'debug', 'updateEqLogicConfig: Completed');
    }
}
