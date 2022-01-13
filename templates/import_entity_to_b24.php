<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/custom.php');

$contactFields = B24Custom::getContactFields();
$contactFieldsJson = json_encode($contactFields);

$allowedFieldIDs = array(
	"EMAIL",
	"NAME",
	"TITLE",
	"PHONE"
);

$allowedFieldIDsJson = json_encode($allowedFieldIDs);
?>

<div class="tab-pane fade" id="import_to_b24" role="tabpanel" aria-labelledby="import-to-b24-tab">
    <br>
    <div class="container-fluid">
        <form id="importEntityToB24Form">

            <fieldset class="form-group" id="importToB24Entity">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('APP_ENTITY') ?></legend>
                    <div class="col-sm-6">
                        <select class="custom-select mb-6" id="importToB24EntitySelect">
                            <option value="<?= B24Common::CONTACT_ENTITY_TYPE_ID ?>"><?= getMessage('APP_CONTACTS') ?></option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-group" id="importFromSendPulseAddressBook">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('SEND_PULSE_BOOK') ?></legend>
                    <div class="col-sm-6">
                        <select class="custom-select mb-3" id="importFromSendPulseBookSelect"></select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-group" id="importToB24Fields">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('APP_ENTITY_FIELDS') ?></legend>
                    <div class="col-sm-6">
                        <select id="importToB24FieldsSelect" name="importToB24FieldsSelect[]" multiple="multiple"
                                data-width="100%">
							<? foreach ($contactFields['result'] as $id => $data): ?>
								<? if ( in_array($id, $allowedFieldIDs)): ?>
                                    <option value="<?= $id ?>" <? if ($id == 'EMAIL'): ?>selected<? endif; ?>>
										<?= $data['isDynamic'] ? $data['listLabel'] : $data['title'] ?>
										<?= " ({$id})" ?>
                                    </option>
								<? endif; ?>
							<? endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-group">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('UPDATE_EXISTING') ?>
                        <div class="short-desc__issue" title="<?= getMessage('UPDATE_EXISTING_DESCRIPTION')?>"></div>
                    </legend>
                    <div class="col-sm-6">
                        <input type="checkbox" id="updateExisting" checked>
                    </div>
                </div>
            </fieldset>

            <div class="form-group row">
                <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary"><?= getMessage('APP_ENTITY_IMPORT') ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#importToB24FieldsSelect').select2(select2Common);
        prohibitionRemovalRequiredFields();
    });

    $(document).on("change", "#importToB24EntitySelect", function () {
        prohibitionRemovalRequiredFields();
    });

    $(document).on("change", "#importToB24FieldsSelect", function () {
        prohibitionRemovalRequiredFields();
    });

    function showDataForImportToB24Tab() {
        request('/ajax/sendpulse/get_address_books.php', 'POST')
            .then(response => {
                response = JSON.parse(response);

                let errorsExist = showErrors(response);

                if (!errorsExist) {
                    $('#importFromSendPulseBookSelect').empty();

                    $('#importFromSendPulseBookSelect').append(`<option value='' selected disabled><?=getMessage("NOT_SELECTED")?></option>`);

                    response.forEach((element) => {
                        $('#importFromSendPulseBookSelect').append(`<option value="${element.id}">${element.name}</option>`);
                    });
                }
            });
    }

    function changeEntityB24InImportEntityToB24() {

        let entityFields = '';

        switch ($('#importToB24EntitySelect').val()) {
            case '3':
                entityFields = <?=$contactFieldsJson?>;
                break;
        }

        entityFields = entityFields['result'];
        let selectData = [];

        let allowedFieldIDs = <?=$allowedFieldIDsJson?>;

        for (let field in entityFields) {

            if (allowedFieldIDs.includes(field)) {
                let newField = {
                    id: field,
                    name: entityFields[field]['isDynamic'] ? entityFields[field]['listLabel'] : entityFields[field]['title']
                };

                if (entityFields[field]['isDynamic']) {
                    newField.name = entityFields[field]['listLabel'];
                } else {
                    newField.name = entityFields[field]['title'];
                }

                newField.name = newField.name + ` (${field})`;

                if (field === 'EMAIL') {
                    newField.selected = true;
                }

                selectData.push(newField);
            }
        }

        let updateData = [];

        selectData.forEach((element) => {
            updateData.push(
                {
                    id: element.id,
                    text: element.name,
                    selected: element.selected ? true : false
                }
            );
        });

        select2Common.data = updateData;
        $("#importToB24FieldsSelect").html('').select2(select2Common);
    }


    function importEntityToB24() {
        if ($("#importToB24EntitySelect").val() == null) {
            let info = {
                errors: "<?=getMessage("NOT_SELECTED_APP_ENTITY")?>"
            };
            showErrors(info, false);
            return;
        }

        if ($("#importFromSendPulseBookSelect").val() == null) {
            let info = {
                errors: "<?=getMessage("NOT_SELECTED_SEND_PULSE_BOOK")?>"
            };
            showErrors(info, false);
            return;
        }

        let data = {
            entity: $("#importToB24EntitySelect").val(),
            address_book: $("#importFromSendPulseBookSelect").val(),
            fields: $("#importToB24FieldsSelect").val(),
            update_existing: $("#updateExisting").is(':checked')
        };

        request('/ajax/sendpulse/import_entity_to_b24.php', 'POST', data)
            .then(response => {
                response = JSON.parse(response);

                let errorsExist = showErrors(response);

                if (!errorsExist) {
                    alert(response['success']);
                }
            });

    }
</script>