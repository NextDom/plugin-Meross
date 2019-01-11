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


/**
 * Plugin installation
 *
 * @return void
 */
function Meross_install()
{
    log::add('meross', 'info', 'Meross installing...');

    cleanPyCache()

    log::add('meross', 'info', 'Meross installation completed.');
}


/**
 * Plugin update
 *
 * @return void
 */
function Meross_update()
{
    log::add('meross', 'info', 'Meross updating...');

    cleanPyCache()

    log::add('meross', 'info', 'Meross update completed.');
}


/**
 * Plugin removal
 *
 * @return void
 */
function Meross_remove()
{
    log::add('meross', 'info', 'Meross removing...');

    log::add('meross', 'info', 'Meross removal completed.');
}


/**
 * Remove __pycache__ from 3rparty folders
 * 
 * @return void
 */
function cleanPyCache()
{
    log::add('meross', 'info', 'Remove __pycache__ from 3rparty folders');

    $3rpartyPath = dirname(__FILE__) . '/../3rparty/';
    exec('cd ' . $3rpartyPath . '; find . | grep -E "(__pycache__|\.pyc|\.pyo$)" | xargs rm -rf');
}
