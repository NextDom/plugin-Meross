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

/* This file is part of NextDom.
*
* NextDom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* NextDom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with NextDom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
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
        $eqLogics = eqLogic::byType('meross', true);

        $stdout = self::launchScript('--refresh --show');
        if ($stdout != null)
        {
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getIsEnable() == 1) {
                    $eqLogic->updateInfo($stdout);
                }
            }
        }else{
            log::add('meross', 'error', 'cron15: No output from script');
        }
        log::add('meross', 'debug', 'cron15: Cron completed.');

    }

    public function postSave()
    {

    }

    public function launchScript($_args)
    {
        $email = config::byKey('merossEmail', 'meross');
        $password = config::byKey('merossPassword', 'meross');
        if ($email == '' || $password == '')
        {
            log::add('meross', 'error', 'shell_exec: Email or password not provided. Please go to plugin configuration.');
            return null;
        }
        try {
            $command = "sudo sh " . self::$_Script . ' --email ' . $email . ' --password ' . $password . ' ' . $_args;
            $log = str_replace($password,'xxx',str_replace($email,'xxx',$command));
            log::add('meross', 'debug', 'shell_exec: ' . $log);
            $stdout = shell_exec($command);
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
    public function getJson($_stdout)
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
    public function getJsonFromFile($_file)
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
    public function syncMeross($_fakeDevices = false)
    {
        log::add('meross', 'debug', 'syncMeross: Load devices from Meross Cloud');
        if ($_fakeDevices == false){
            $stdout = self::launchScript('--refresh --show');
            $json = self::getJson($stdout);
        }else{
            log::add('meross', 'debug', 'syncMeross: Load fake devices for developement');
            $json = self::getJsonFromFile(self::$_FakeJson);
        }
        
        log::add('meross', 'debug', $json);
        foreach ($json as $key=>$devices) {
            $device = self::byLogicalId($key, 'meross');
            if (!is_object($device)) {
                log::add('meross', 'debug','syncMeross: Add device=' . $devices["name"]);
                $device = new self();
                $device->setName($devices["name"]);
                $device->setEqType_name("meross");
                $device->setLogicalId($key);
                $device->setConfiguration('type', $devices["type"]);
                $device->setConfiguration('ip', $devices["ip"]);
                $device->setConfiguration('mac', $devices["mac"]);
                $device->setConfiguration('online', $devices["online"]);
                $device->setConfiguration('appname', $devices["name"]);
                $device->setConfiguration('firmversion', $devices["firmversion"]);
                $device->setConfiguration('hardversion', $devices["hardversion"]);
                $device->save();

                // Charge la définition du device
                $jsonCmd = self::getJsonFromFile(__DIR__ . '/../../core/config/devices/'.$devices["type"].'/def.json');
                foreach($jsonCmd['commands'] as $key=>$commandes){
                    $cmd = $device->getCmd(null, $commandes['logicalId']);
                    if (!is_array($cmd) || !is_object($cmd) ) {
                        log::add('meross', 'debug','syncMeross: - Add cmd=' .$commandes['logicalId']);
                        $cmd = new merossCmd();
                        $cmd->setLogicalId($commandes['logicalId']);
                        $cmd->setName(__($commandes['name'], __FILE__));
                        $cmd->setType($commandes['type']);
                        $cmd->setSubType($commandes['subtype']);
                        $cmd->setEqLogic_id($device->getId());
                        $cmd->setIsVisible($commandes['isVisible']);
                        if(isset($commandes['isHistorized']) && $commandes['type'] == 'info')
                        {
                            $cmd->setIsHistorized($commandes['isHistorized']);
                        } else {
                            $cmd->setIsHistorized(false);
                        }
                        $cmd->setDisplay('generic_type', $commandes['display']['generic_type']);
                        $cmd->setTemplate('dashboard', $commandes['template']['dashboard']);
                        $cmd->setTemplate('mobile', $commandes['template']['mobile']);

                        $cmd->save();
                        
                        $splitCommandes = explode("_", $commandes['logicalId']);
                        if ($splitCommandes[0] == 'onoff'){
                            // Mémorise l'ID de la cmd onoff_x pour affecter aux cmd "on_x" & "off_x" 
                            $etatid =  $cmd->getId();
                        } elseif ($splitCommandes[0] == 'on' || $splitCommandes[0] == 'off' )
                        {
                            // Affecte l'ID de la cmd onoff_x en value
                            log::add('meross', 'debug','syncMeross: - Set value : ' .$commandes["value"]);
                            $cmd->setValue($etatid);
                            $cmd->save();
                        }
                    }
                }
            } else {
                log::add('meross', 'debug','syncMeross: ' . $devices["name"] . ' already exist.');
            }
        }

        log::add('meross', 'debug', 'syncMeross: synchronization completed.');
    }



    public function updateInfo($_stdout)
    {
        
        log::add('meross', 'debug','updateInfo: ' . $_stdout );

        try {
            $infos = self::getJson($_stdout);
        } catch (\Exception $e) {
            log::add('meross', 'error', $e);
            return;
        }

        foreach ($infos as $key=>$devices) {
            if ($key == $this->getLogicalId()) {
                foreach ($devices as $key2=>$Commands) {
                    $cmd = $this->getCmd(null, $key2);
                    if (!is_array($cmd) || !is_object($cmd) ) {
                        if($key2 == "onoff") {
                            foreach ($Commands as $key3=>$value) {
                                log::add('meross', 'debug', 'updateInfo: -channel_' . $key3 .'=' .  $value);
                                $this->checkAndUpdateCmd("onoff_".$key3, $value);
                            }
                        }else{
                            log::add('meross', 'debug', 'updateInfo: -' . $key2 .'=' . $devices[$key2]);
                            $this->checkAndUpdateCmd($key2, $devices[$key2]);
                        }
                    }
                }

                log::add('meross', 'debug', 'updateInfo: -Update eqLogic informations');
                $this->setConfiguration('ip', $devices['ip']);
                $this->setConfiguration('online', $devices['online']);
                $this->setConfiguration('firmversion', $devices['firmversion']);
                $this->setConfiguration('hardversion', $devices['hardversion']);
                $this->setConfiguration('appname', $devices['name']);
                $this->save();
            }
        }

        log::add('meross', 'debug', 'updateInfo: Update completed');
    }

}
