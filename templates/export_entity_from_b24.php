<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/custom.php');

$contactFields = B24Custom::getContactFields();
$contactFieldsJson = json_encode($contactFields);
$companyFieldsJson = json_encode(B24Custom::getCompanyFields());

$badFieldTypes = array(
	"file",
	"user",
	"char",
	"crm_status",
	"crm_currency",
	"double"
);
$badFieldIDs = array(
	"ID",
	"ORIGINATOR_ID",
	"ORIGIN_ID",
	"ORIGIN_VERSION",
	"FACE_ID",
	"UTM_SOURCE",
	"UTM_MEDIUM",
	"UTM_CAMPAIGN",
	"UTM_CONTENT",
	"UTM_TERM",
	"UF_CRM_SP_UNSUBSCR"
);

$badFieldTypesJson = json_encode($badFieldTypes);
$badFieldIDsJson = json_encode($badFieldIDs);
?>

<div class="tab-pane fade" id="export_from_b24" role="tabpanel" aria-labelledby="export-from-b24-tab">
    <br>
    <div class="container-fluid">
        <form id="exportEntityFromB24Form">
            <fieldset class="form-group" id="exportFromB24Entity">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('APP_ENTITY') ?></legend>
                    <div class="col-sm-6">
                        <select class="custom-select mb-6" id="exportFromB24EntitySelect">
                            <option value="<?= B24Common::CONTACT_ENTITY_TYPE_ID ?>"><?= getMessage('APP_CONTACTS') ?></option>
                            <option value="<?= B24Common::COMPANY_ENTITY_TYPE_ID ?>"><?= getMessage('APP_COMPANIES') ?></option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-group" id="exportToSendPulseAddressBook">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('SEND_PULSE_BOOK') ?></legend>
                    <div class="col-sm-6">
                        <select class="custom-select mb-3" id="exportToSendPulseBookSelect"></select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-group" id="exportFromB24Fields">
                <div class="row">
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('APP_ENTITY_FIELDS') ?></legend>
                    <div class="col-sm-6">
                        <select id="exportFromB24FieldsSelect" name="exportFromB24FieldsSelect[]" multiple="multiple"
                                data-width="100%">
							<? foreach ($contactFields['result'] as $id => $data): ?>
								<? if (!in_array($data['type'], $badFieldTypes) && !in_array($id, $badFieldIDs)): ?>
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
                    <legend class="col-form-label col-sm-3 pt-0"><?= getMessage('EXPORT_DUPLICATES') ?>
                        <div class="short-desc__issue" title="<?= getMessage('EXPORT_DUPLICATES_DESCRIPTION') ?>"></div>
                    </legend>
                    <div class="col-sm-6">
                        <input type="checkbox" id="exportDuplicates" checked>
                    </div>
                </div>
            </fieldset>

            <div class="form-group row">
                <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary"><?= getMessage('APP_ENTITY_EXPORT') ?></button>
                </div>
            </div>
        </form>
    </div>

</div>

<script>
    $(document).ready(function () {
        $('#exportFromB24FieldsSelect').select2(select2Common);
        prohibitionRemovalRequiredFields();
    });

    $(document).on("change", "#exportFromB24EntitySelect", function () {
        prohibitionRemovalRequiredFields();
    });

    $(document).on("change", "#exportFromB24FieldsSelect", function () {
        prohibitionRemovalRequiredFields();
    });
    
    function showDataForExportFromB24Tab() {
        request('/ajax/sendpulse/get_address_books.php', 'POST')
            .then(response => {
                response = JSON.parse(response);

                let errorsExist = showErrors(response);

                if (!errorsExist) {
                    $('#exportToSendPulseBookSelect').empty();

                    $('#exportToSendPulseBookSelect').append(`<option value='' selected disabled><?=getMessage("NOT_SELECTED")?></option>`);

                    response.forEach((element) => {
                        $('#exportToSendPulseBookSelect').append(`<option value="${element.id}">${element.name}</option>`);
                    });
                }
            });
    }

    function changeEntityB24InExportEntityFromB24() {

        let entityFields = '';

        switch ($('#exportFromB24EntitySelect').val()) {
            case '3':
                entityFields = <?=$contactFieldsJson?>;
                break;
            case '4':
                entityFields = <?=$companyFieldsJson?>;
                break;
        }

        entityFields = entityFields['result'];
        let selectData = [];

        let badFieldTypes = <?=$badFieldTypesJson?>;
        let badFieldIDs = <?=$badFieldIDsJson?>;

        for (let field in entityFields) {

            if (!badFieldTypes.includes(entityFields[field]['type']) && !badFieldIDs.includes(field)) {
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
        $("#exportFromB24FieldsSelect").html('').select2(select2Common);
    }

    function exportEntityFromB24() {

        if ($("#exportFromB24EntitySelect").val() == null) {
            let info = {
                errors: "<?=getMessage("NOT_SELECTED_APP_ENTITY")?>"
            };
            showErrors(info, false);
            return;
        }

        if ($("#exportToSendPulseBookSelect").val() == null) {
            let info = {
                errors: "<?=getMessage("NOT_SELECTED_SEND_PULSE_BOOK")?>"
            };
            showErrors(info, false);
            return;
        }

        let data = {
            entity: $("#exportFromB24EntitySelect").val(),
            address_book: $("#exportToSendPulseBookSelect").val(),
            fields: $("#exportFromB24FieldsSelect").val(),
            export_duplicates: $("#exportDuplicates").is(':checked')
        };

        request('/ajax/sendpulse/export_entity_from_b24.php', 'POST', data)
            .then(response => {
                response = JSON.parse(response);

                let errorsExist = showErrors(response);

                if (!errorsExist) {
                    alert(response['success']);
                }
            });
    }
</script>