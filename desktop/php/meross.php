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

if (!isConnect('admin')) {
    throw new \Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('meross');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <li class="filter" style="margin-bottom: 5px;">
                    <input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%" />
                </li>
                <?php
                    foreach ($eqLogics as $eqLogic) {
                        $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                        echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                    }
                    ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction" data-action="sync" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <i class="fa fa-sync" style="font-size : 6em;color:#33b8cc;"></i>
                <br>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">{{ Synchroniser }}</span>
            </div>
            <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
                <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
                <br>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">{{ Configuration }}</span>
            </div>
            <div class="cursor" id="bt_healthmeross" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px; border-radius: 2px;width : 160px;margin-left : 10px;">
                <i class="fa fa-medkit" style="font-size : 6em;color:#767676;"></i>
                <br>
                <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">{{ Santé }}</span>
            </div>
        </div>

        <br>

        <legend><i class="fa fa-table"></i> {{Mes Meross}}</legend>
        <div class="eqLogicThumbnailContainer">
            <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                    if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('type') . '/icon.png')) {
                        echo '<img src="plugins/meross/core/config/devices/' . $eqLogic->getConfiguration('type') . '/icon.png' . '" height="105" width="105" />';
                      } else {
                        echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
                      }
                    echo "<br>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
                    echo '</div>';
                }
            ?>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="display: none;">
        <section class="content-header">
            <div class="action-bar">
                <div class="action-group">
                    <a class="btn btn-danger btn-action-bar eqLogicAction" data-action="returnToThumbnailDisplay"><i class="fas fa-chevron-left">&nbsp;&nbsp;</i>Retour</a>
                </div>
                <div class="action-group">
                    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle">&nbsp;&nbsp;</i>{{Sauvegarder}}</a>
                    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs">&nbsp;&nbsp;</i>{{Configuration avancée}}</a>
                    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle">&nbsp;&nbsp;</i>{{Supprimer}}</a>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs pull-right" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab">
                            <i class="fa fa-tachometer"></i> {{Equipement}}
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab">
                            <i class="fa fa-list-alt"></i> {{Commandes}}
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                        <br />
                        <div class="row">
                        <!-- Configuration -->
                            <div class="col-md-8">
                                <div class="box box-widget widget-user-2">
                                    <div class="widget-user-header backgroundColor">
                                        <h3 class="eqlogic-box-title">{{ Configuration équipement }}</h3>
                                    </div>
                                    <div class="box-body">
                                        <form class="form-horizontal">
                                            <fieldset>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label" for="name">{{Nom de l'équipement Meross}}</label>
                                                    <div class="col-sm-3">
                                                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" id="name" placeholder="{{Nom de l'équipement Meross}}" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label" for="sel_object">{{Objet parent}}</label>
                                                    <div class="col-sm-3">
                                                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                                            <option value="">{{Aucun}}</option>
                                                            <?php
                                                                foreach (jeeObject::all() as $object) {
                                                                    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label">{{Catégorie}}</label>
                                                    <div class="col-sm-9">
                                                        <?php
                                                            foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                                                echo '<label class="checkbox-inline" for="category-' . $key . '">';
                                                                echo '<input type="checkbox" id="category-' . $key . '" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                                                echo '</label>';
                                                            }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label"></label>
                                                    <div class="col-sm-9">
                                                        <label class="checkbox-inline" for="is-enable">
                                                            <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked="checked" id="is-enable" />
                                                            {{Activer}}
                                                        </label>
                                                        <label class="checkbox-inline" for="is-visible">
                                                            <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked="checked" id="is-visible" />
                                                            {{Visible}}
                                                        </label>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <!-- End Configuration -->

                        <!-- Informations -->
                            <div class="col-md-4">
                                <div class="box box-widget widget-user-2">
                                    <div class="widget-user-header backgroundColor">
                                        <h3 class="eqlogic-box-title">{{ Informations Meross Cloud }}</h3>
                                    </div>
                                    <div class="box-footer no-padding">
                                        <ul class="nav nav-stacked">
                                            <li><a href="#">{{ Modèle }}<span class="eqLogicAttr pull-right badge bg-blue" data-l1key="configuration" data-l2key="type"></span></a></li>
                                            <li><a href="#">{{ Adresse IP }}<span class="eqLogicAttr pull-right badge bg-default" data-l1key="configuration" data-l2key="ip"></span></a></li>
                                            <li><a href="#">{{ Adresse MAC }}<span class="eqLogicAttr pull-right badge bg-default" data-l1key="configuration" data-l2key="mac"></span></a></li>
                                            <li><a href="#">{{ En ligne }}<span class="eqLogicAttr pull-right badge bg-green" data-l1key="configuration" data-l2key="online"></span></a></li>
                                            <li><a href="#">{{ Nom sur l'app Meross }}<span class="eqLogicAttr pull-right badge bg-default" data-l1key="configuration" data-l2key="appname"></span></a></li>
                                            <li><a href="#">{{ Firmware version }}<span class="eqLogicAttr pull-right badge bg-default" data-l1key="configuration" data-l2key="firmversion"></span></a></li>
                                            <li><a href="#">{{ Hardware version }}<span class="eqLogicAttr pull-right badge bg-default" data-l1key="configuration" data-l2key="hardversion"></span></a></li>
                                            <li><a href="#">{{ UUID }}<span class="eqLogicAttr pull-right badge bg-default" data-l1key="logicalId"></span></a></li>
                                            <li>
                                                <a href="#">
                                                    <img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive img-model" />
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <!-- End Informations -->
                                                        </div>

                    </div>
                    <div role="tabpanel" class="tab-pane" id="commandtab">
                        <table id="table_cmd" class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width:10%">{{#}}</th>
                                    <th style="width:40%">{{Nom}}</th>
                                    <th style="width:20%">{{Paramètres}}</th>
                                    <th style="width:20%">{{Options}}</th>
                                    <th style="width:10%">{{Actions}}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>                                                
    </div>
</div>

<?php
include_file('desktop', 'meross', 'js', 'meross');
include_file('desktop', 'meross', 'css', 'meross');
include_file('core', 'plugin.template', 'js');
