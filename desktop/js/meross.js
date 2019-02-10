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


$("#table_cmd").sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
});

function printEqLogic(_eqLogic) {
    if (_eqLogic.id != '') {
        $('#img_device').attr("src", $('.eqLogicDisplayCard[data-eqLogic_id=' + _eqLogic.id + '] img').attr('src'));
    } else {
        $('#img_device').attr("src", 'plugins/meross/plugin_info/meross_icon.png');
    }
}

$('#bt_healthmeross').on('click', function () {
    $('#md_modal').dialog({title: "{{Santé Meross}}"});
    $('#md_modal').load('index.php?v=d&plugin=meross&modal=health').dialog('open');
});

$('.eqLogicAction[data-action=sync]').on('click', function () {
    $('#div_alert').showAlert({message: '{{Synchronisation avec le cloud Meross en cours...}}', level: 'warning'});
    $.post({
        url: 'plugins/meross/core/ajax/meross.ajax.php',
        data: {
            action: 'syncMeross'
        },
        success: function (data, status) {
            // Test si l'appel a échoué
            if (data.state !== 'ok' || status !== 'success') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Synchronisation terminée}}', level: 'success'});
            window.location.reload();

        },
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        }
    });
});

$('.eqLogicAction[data-action=applydef]').on('click', function () {
    var dialog_title = '{{Recharger la configuration}}';
    var dialog_message = '<form class="form-horizontal onsubmit="return false;"> ';
    dialog_message += '<label class="control-label" > {{Sélectionner le mode de rechargement de la configuration ?}} </label> ' +
    '<div> <div class="radio"> <label > ' +
    '<input type="radio" name="command" id="command-0" value="0" checked="checked"> {{Sans supprimer les commandes}} </label> ' +
    '</div><div class="radio"> <label > ' +
    '<input type="radio" name="command" id="command-1" value="1"> {{En supprimant et recréant les commandes}}</label> ' +
    '</div> ' +
    '</div><br>' +
    '<label class="lbl lbl-warning" for="name">{{Attention, "En supprimant et recréant" va supprimer les commandes existantes.}}</label> ';
    dialog_message += '</form>';
    bootbox.dialog({
       title: dialog_title,
       message: dialog_message,
       buttons: {
           "{{Annuler}}": {
               className: "btn-danger",
               callback: function () {
               }
           },
           success: {
               label: "{{Démarrer}}",
               className: "btn-success",
               callback: function () {
                    if ($("input[name='command']:checked").val() == "1"){
						bootbox.confirm('{{Etes-vous sûr de vouloir récréer toutes les commandes ? Cela va supprimer les commandes existantes}}', function (result) {
                            if (result) {
                                $.ajax({
                                    type: "POST",
                                    url: "plugins/meross/core/ajax/meross.ajax.php",
                                    data: {
                                        action: "applyDef",
                                        id: $('.eqLogicAttr[data-l1key=id]').value(),
                                        deleteCommand: 1,
                                    },
                                    dataType: 'json',
                                    global: false,
                                    error: function (request, status, error) {
                                        handleAjaxError(request, status, error);
                                    },
                                    success: function (data) {
                                        if (data.state != 'ok') {
                                            $('#div_alert').showAlert({message: data.result, level: 'danger'});
                                            return;
                                        }
                                        $('#div_alert').showAlert({message: '{{Opération réalisée avec succès}}', level: 'success'});
                                        $('.li_eqLogic[data-eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value() + ']').click();
                                    }
                                });
                            }
                        });
					} else {
						$.ajax({
                                    type: "POST",
                                    url: "plugins/meross/core/ajax/meross.ajax.php",
                                    data: {
                                        action: "applyDef",
                                        id: $('.eqLogicAttr[data-l1key=id]').value(),
                                        deleteCommand: 0,
                                    },
                                    dataType: 'json',
                                    global: false,
                                    error: function (request, status, error) {
                                        handleAjaxError(request, status, error);
                                    },
                                    success: function (data) {
                                        if (data.state != 'ok') {
                                            $('#div_alert').showAlert({message: data.result, level: 'danger'});
                                            return;
                                        }
                                        $('#div_alert').showAlert({message: '{{Opération réalisée avec succès}}', level: 'success'});
                                        $('.li_eqLogic[data-eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value() + ']').click();
                                    }
                                });
					}
            }
        },
    }
});
});

/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.Meross
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    // ID
    tr += '<td>';
    tr += '  <span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    // Icon + Name
    tr += '<td>';
    tr += '  <div class="row">';
    tr += '    <div class="col-sm-3">';
    tr += '      <a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> {{Icône}}</a>';
    tr += '      <span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '    </div>';
    tr += '    <div class="col-sm-9">';
    tr += '      <input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '    </div>';
    tr += '  </div>';
    tr += '</td>';
    //
    tr += '<td style="display : none;">';
    tr += '  <select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
    tr += '     <option value="">Aucune</option>';
    tr += '  </select>';
    tr += '  <span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '  <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    if (init(_cmd.type) == 'action'){
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" placeholder="{{Commande}}" >';
    }
    tr += '  <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateValue" placeholder="{{Valeur retour d\'état}}" style="margin-top : 5px;">';
    tr += '  <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateTime" placeholder="{{Durée avant retour d\'état (min)}}" style="margin-top : 5px;">';
    tr += '</td>';
    // Visible + Historized + Inverted
    tr += '<td>';
    tr += '   <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '   <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    tr += '   <span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
    tr += '</td>';
    // Unit + Min + Max
    tr += '<td>';
    tr += '  <div class="row">';
    tr += '    <div class="col-xs-12 col-lg-4">';
    tr += '      <input class="cmdAttr form-control" data-l1key="unite" placeholder="Unité" title="{{Unité}}">';
    tr += '    </div>';
    tr += '    <div class="col-xs-12 col-lg-4">';
    tr += '      <input class="cmdAttr form-control" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}">';
    tr += '    </div>';
    tr += '    <div class="col-xs-12 col-lg-4">';
    tr += '      <input class="cmdAttr form-control" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}">';
    tr += '    </div>';
    tr += '  </div>';
    tr += '</td>';
    // Actions
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $('.eqLogicAttr[data-l1key=id]').value(),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
}