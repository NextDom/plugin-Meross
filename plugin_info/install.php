<?php

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function meross_update() {
    foreach (eqLogic::byType('meross') as $meross) {
        $meross->save();
    }
}

?>
