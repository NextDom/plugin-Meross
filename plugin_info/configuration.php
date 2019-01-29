<?php

/*
 * This file is part of the NextDom software (https://github.com/NextDom or http://nextdom.github.io).
 * Copyright (c) 2018 NextDom.
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

include_file('core', 'authentification', 'php');

if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}

?>

<form class="form-horizontal">
    <fieldset>
        <div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-xs-12">
            <div class="form-group">
                <label for="merossEmail">{{Email}}</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="configKey form-control" placeholder="{{Email de votre compte Meross}}"
                        data-l1key="merossEmail" id="merossEmail">
                </div>
            </div>
        </div>

        <br>

        <div class="col-lg-4 col-lg-offset-4 col-md-6 col-md-offset-3 col-xs-12">
            <div class="form-group">
                <label for="merossPassword">{{Mot de passe}}</label>
                <div class="input-group">
                    <span class="input-group-addon"><i class="fas fa-key"></i></span>
                    <input type="password" class="configKey form-control" placeholder="{{Mot de passe associé}}"
                        data-l1key="merossPassword" id="merossPassword">
                </div>
            </div>
        </div>
    </fieldset>
</form>