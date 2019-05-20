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

require_once __DIR__ . '/../../../core/php/core.inc.php';

function BoschIndego_install() {
  $cron = cron::byClassAndFunction('BoschIndego', 'cronBoschIndego');
  if (!is_object($cron)) {
    log::add('BoschIndego','debug',__FUNCTION__ .' Creating cronBoschIndego entry');
    $cron = new cron();
    $cron->setClass('BoschIndego');
    $cron->setFunction('cronBoschIndego');
    $cron->setEnable(0);
    $cron->setDeamon(0);
    $cron->setSchedule('* * * * *');
    $cron->save();
  }
}

function BoschIndego_update() {
  $cron = cron::byClassAndFunction('BoschIndego', 'cronBoschIndego');
  if (!is_object($cron)) {
    log::add('BoschIndego','debug',__FUNCTION__ .' Creating cronBoschIndego entry');
    $cron = new cron();
    $cron->setClass('BoschIndego');
    $cron->setFunction('cronBoschIndego');
    $cron->setEnable(0);
    $cron->setDeamon(0);
    $cron->setSchedule('* * * * *');
    $cron->save();
  }
}

function BoschIndego_remove() {
  $cron = cron::byClassAndFunction('BoschIndego', 'cronBoschIndego');
  if (is_object($cron)) {
      log::add('BoschIndego','debug',__FUNCTION__ .' Removing cronBoschIndego entry');
      $cron->remove();
  }
}

