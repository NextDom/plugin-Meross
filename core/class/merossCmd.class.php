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

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class merossCmd extends cmd
{
    public function execute($_options = array())
    {
        $eqLogic = $this->getEqLogic();
        $action = $this->getLogicalId();
        log::add('meross', 'debug','action: '. $action );
        $email = config::byKey('merossEmail', 'meross');
        $password = config::byKey('merossPassword', 'meross');
        $command = 'sudo sh ' . __DIR__ . '/../../3rdparty/meross.sh' . ' --email ' . $email . ' --password ' . $password . ' --uuid ' . $eqLogic->getLogicalId();
        $log = str_replace($password,'xxx',str_replace($email,'xxx',$command));

        $splitAction = explode("_", $action);

        if($splitAction[0] == "on") {
            $command = $command . ' --set_on --channel ' . $splitAction[1] ;
            log::add('meross','debug','shell_exec:' . $log);
            $result=trim(shell_exec($command));
            $eqLogic->cron($eqLogic->getId());

        } elseif ($splitAction[0] == "off") {
            $command = $command . ' --set_off --channel ' . $splitAction[1];
            log::add('meross','debug','shell_exec:' . $log);
            $result=trim(shell_exec($command));
            $eqLogic->cron($eqLogic->getId());
        }

        if ($action == 'refresh') {
            $eqLogic->cron($eqLogic->getId());
        }
    }
}
