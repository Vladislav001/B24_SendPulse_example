<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/custom.php');

$contactFields = B24Custom::getContactFields();
$companyFields = B24Custom::getCompanyFields();

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

<div class="tab-pane fade" id="auto_export_from_b24" role="tabpanel" aria-labelledby="auto-export-from-b24-tab">
    <br>
    <div class="container-fluid">

        <div class="row">
            <div class="col-sm-2"><b><?=getMessage("ENTITY")?></b></div>
            <div class="col-sm-3"><b><?=getMessage("BOOK")?></b></div>
            <div class="col-sm-5"><b><?=getMessage("FIELDS")?></b></div>
        </div>

        <hr>

        <form id="autoExportContactsFromB24Form">

            <fieldset class="form-group">
                <div class="row">
                    <legend class="col-form-label col-sm-1 pt-0"><?= getMessage('APP_CONTACTS') ?></legend>
                    <div class="col-sm-1">
                        <input type="checkbox" id="autoExportContacts">
                    </div>
                    <div class="col-sm-3">
                        <select class="custom-select mb-3 autoExportFromB24EntitySelect" id="autoExportContactsToSendPulseBookSelect"></select>
                    </div>
                    <div class="col-sm-5">
                        <select id="autoExportContactsFromB24FieldsSelect"
                                name="autoExportContactsFromB24FieldsSelect[]" multiple="multiple"
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
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary"><?= getMessage('APP_CONNECTION_SAVE') ?></button>
                    </div>
                </div>
            </fieldset>

        </form>

        <form id="autoExportCompaniesFromB24Form">

            <fieldset class="form-group">
                <div class="row">
                    <legend class="col-form-label col-sm-1 pt-0"><?= getMessage('APP_COMPANIES') ?></legend>
                    <div class="col-sm-1">
                        <input type="checkbox" id="autoExportCompanies">
                    </div>
                    <div class="col-sm-3">
                        <select class="custom-select mb-3 autoExportFromB24EntitySelect" id="autoExportCompaniesToSendPulseBookSelect"></select>
                    </div>
                    <div class="col-sm-5">
                        <select id="autoExportCompaniesFromB24FieldsSelect"
                                name="autoExportCompaniesFromB24FieldsSelect[]" multiple="multiple"
                                data-width="100%">
							<? foreach ($companyFields['result'] as $id => $data): ?>
								<? if (!in_array($data['type'], $badFieldTypes) && !in_array($id, $badFieldIDs)): ?>
                                    <option value="<?= $id ?>" <? if ($id == 'EMAIL'): ?>selected<? endif; ?>>
										<?= $data['isDynamic'] ? $data['listLabel'] : $data['title'] ?>
										<?= " ({$id})" ?>
                                    </option>
								<? endif; ?>
							<? endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary"><?= getMessage('APP_CONNECTION_SAVE') ?></button>
                    </div>
                </div>
            </fieldset>

        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#autoExportContactsFromB24FieldsSelect').select2(select2Common);
        prohibitionRemovalRequiredFields();
    });

    $(document).on("change", "#autoExportContactsFromB24FieldsSelect", function () {
        prohibitionRemovalRequiredFields();
    });

    $(document).ready(function () {
        $('#autoExportCompaniesFromB24FieldsSelect').select2(select2Common);
        prohibitionRemovalRequiredFields();
    });

    $(document).on("change", "#autoExportCompaniesFromB24FieldsSelect", function () {
        prohibitionRemovalRequiredFields();
    });
</script>