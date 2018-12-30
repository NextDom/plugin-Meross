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

class meross extends eqLogic {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true);
	private static $_merosss = null;

	/*     * ***********************Methode static*************************** */

	public static function cron($_eqlogic_id = null) {
		$eqLogics = ($_eqlogic_id !== null) ? array(eqLogic::byId($_eqlogic_id)) : eqLogic::byType('meross', true);
		foreach ($eqLogics as $meross) {
			try {
				$meross->getmerossInfo();
			} catch (Exception $e) {

			}
		}
		foreach ($eqLogics as $meross) {
			try {
				$meross->disconnect();
			} catch (Exception $e) {

			}
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function getmerossInfo() {
		if (!is_array(self::$_merosss) || !isset(self::$_merosss[$this->getConfiguration('addr')]) || !isset(self::$_merosss[$this->getConfiguration('addr')]['infos']) || !is_array(self::$_merosss[$this->getConfiguration('addr')])) {
			$cmd = 'curl -s -b "AIROS_SESSIONID=' . $this->connect() . '" ' . $this->getConfiguration('addr') . '/sensors';
			self::$_merosss[$this->getConfiguration('addr')]['infos'] = json_decode(exec($cmd), true);
		}
		log::add('meross', 'debug', print_r(self::$_merosss[$this->getConfiguration('addr')]['infos'], true));
		if (!is_array(self::$_merosss[$this->getConfiguration('addr')]['infos']['sensors'])) {
			return;
		}
		foreach (self::$_merosss[$this->getConfiguration('addr')]['infos']['sensors'] as $sensor) {
			if ($sensor['port'] != $this->getLogicalId()) {
				continue;
			}
			$this->checkAndUpdateCmd('etat', $sensor['output']);
			$this->checkAndUpdateCmd('power', round($sensor['power'], 2));
			$this->checkAndUpdateCmd('voltage', round($sensor['voltage'], 2));
			$this->checkAndUpdateCmd('current', round($sensor['current'], 2));
			$this->checkAndUpdateCmd('powerfactor', round($sensor['powerfactor'], 2));
			$this->checkAndUpdateCmd('energy', round($sensor['energy'] / 1000, 2));
		}
	}

	public function preInsert() {
		$this->setCategory('energy', 1);
	}

	public function postSave() {
		$cmd = $this->getCmd(null, 'etat');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('etat');
			$cmd->setName(__('Etat', __FILE__));
			$cmd->setIsVisible(0);
		}
		$cmd->setType('info');
		$cmd->setDisplay('generic_type', 'ENERGY_STATE');
		$cmd->setSubType('binary');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();
		$cmdid = $cmd->getId();

		$cmd = $this->getCmd(null, 'power');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('power');
			$cmd->setIsVisible(1);
			$cmd->setIsHistorized(1);
			$cmd->setName(__('Puissance', __FILE__));
			$cmd->setUnite('W');
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(1);
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'voltage');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('voltage');
			$cmd->setUnite('V');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Voltage', __FILE__));
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(5);
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'current');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('current');
			$cmd->setUnite('A');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Courant', __FILE__));
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(3);
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'energy');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('energy');
			$cmd->setUnite('kWh');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Consommation', __FILE__));
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(2);
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'powerfactor');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('powerfactor');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Facteur Puissance', __FILE__));
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(4);
		}
		$cmd->setType('info');
		$cmd->setSubType('numeric');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();

		$cmd = $this->getCmd(null, 'on');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('on');
			$cmd->setName(__('On', __FILE__));
			$cmd->setOrder(0);
			$cmd->setTemplate('dashboard', 'prise');
		}
		$cmd->setTemplate('mobile', 'prise');
		$cmd->setType('action');
		$cmd->setDisplay('generic_type', 'ENERGY_ON');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setValue($cmdid);
		$cmd->save();

		$cmd = $this->getCmd(null, 'off');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('off');
			$cmd->setName(__('Off', __FILE__));
			$cmd->setOrder(0);
			$cmd->setTemplate('dashboard', 'prise');
		}
		$cmd->setTemplate('mobile', 'prise');
		$cmd->setType('action');
		$cmd->setDisplay('generic_type', 'ENERGY_OFF');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->setValue($cmdid);
		$cmd->save();

		$cmd = $this->getCmd(null, 'refresh');
		if (!is_object($cmd)) {
			$cmd = new merossCmd();
			$cmd->setLogicalId('refresh');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Rafraichir', __FILE__));
		}
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->setEqLogic_id($this->getId());
		$cmd->save();
	}

	public function connect() {
		if (!is_array(self::$_merosss) || !isset(self::$_merosss[$this->getConfiguration('addr')])) {
			self::$_merosss[$this->getConfiguration('addr')] = array('sessid' => null, 'infos' => null);
		}
		if (self::$_merosss[$this->getConfiguration('addr')]['sessid'] != null) {
			return self::$_merosss[$this->getConfiguration('addr')]['sessid'];
		}
		self::$_merosss[$this->getConfiguration('addr')]['sessid'] = mt_rand(1, 9);
		for ($i = 0; $i < 31; $i++) {
			self::$_merosss[$this->getConfiguration('addr')]['sessid'] .= mt_rand(0, 9);
		}
		shell_exec('curl -X POST -d "username=' . $this->getConfiguration('user') . '&password=' . $this->getConfiguration('pwd') . '" -b "AIROS_SESSIONID=' . self::$_merosss[$this->getConfiguration('addr')]['sessid'] . '" ' . $this->getConfiguration('addr') . '/login.cgi  2>&1 >> /dev/null');
		return self::$_merosss[$this->getConfiguration('addr')]['sessid'];
	}

	public function disconnect() {
		if (!is_array(self::$_merosss) || !isset(self::$_merosss[$this->getConfiguration('addr')]) || self::$_merosss[$this->getConfiguration('addr')]['sessid'] == null) {
			return;
		}
		shell_exec('curl -b "AIROS_SESSIONID=' . self::$_merosss[$this->getConfiguration('addr')]['sessid'] . '" ' . $this->getConfiguration('addr') . '/logout.cgi 2>&1 >> /dev/null');
		self::$_merosss[$this->getConfiguration('addr')]['sessid'] = null;
	}
}

class merossCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = null) {
		$eqLogic = $this->getEqlogic();
		if ($this->getLogicalId() == 'refresh') {
			return meross::cron($eqLogic->getId());
		}
		$output = $this->getLogicalId() == 'on' ? '1' : '0';
		exec('curl -X PUT -d "output=' . $output . '" -b "AIROS_SESSIONID=' . $eqLogic->connect() . '" ' . $eqLogic->getConfiguration('addr') . '/sensors/' . $eqLogic->getLogicalId());
		sleep(1);
		$eqLogic->getmerossInfo();
		$eqLogic->disconnect();
	}

	/*     * **********************Getteur Setteur*************************** */
}
?>
