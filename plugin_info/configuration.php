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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nom d'utilisateur}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="username" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Mot de passe}}</label>
            <div class="col-lg-2">
                <input type="password" class="configKey form-control" data-l1key="password" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label"></label>
            <div class="col-lg-2">
                <span class="col-lg-4"><a class="btn btn-sm btn-info" id="btn-test_connection"><i class="fas fa-magic"></i> {{Test connexion}}</a></span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Numéros de série}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="almSnList" />
            </div>
        </div>
  </fieldset>
</form>
<script>
$('#btn-test_connection').on('click',function(){
    $('#md_modal2').dialog({title: "{{Test de connexion Bosch Indego}}"});
    $('#md_modal2').load('index.php?v=d&plugin=BoschIndego&modal=authenticate').dialog('open');
 })
</script>
