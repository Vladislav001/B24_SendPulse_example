<script src="//api.bitrix24.com/api/v1/"></script>
<link href="../templates/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
<script src="../templates/libs/jquery/jquery-3.5.1.slim.min.js"></script>
<script src="../templates/libs/bootstrap/js/bootstrap.min.js"></script>
<script src="../templates/libs/jquery/jquery-3.4.1.min.js"></script>
<link href="../templates/libs/select2/css/select2.min.css" rel="stylesheet"/>
<script src="../templates/libs/select2/js/select2.min.js"></script>
<link href="../templates/css/main.css" rel="stylesheet"/>


<ul class="nav nav-tabs" id="tabNav" role="tablist">
    <li class="nav-item">
        <a class="nav-link active disabled" id="connection-tab" data-toggle="tab" href="#connection" role="tab"
           aria-controls="connection"
           aria-selected="true"><?= getMessage('APP_PANEL_CONNECTION') ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" id="export-from-b24-tab" data-toggle="tab" href="#export_from_b24" role="tab"
           aria-controls="export_from_b24"
           aria-selected="false"><?= getMessage('APP_PANEL_EXPORT_FROM_B24') ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" id="auto-export-from-b24-tab" data-toggle="tab" href="#auto_export_from_b24"
           role="tab"
           aria-controls="auto_export_from_b24"
           aria-selected="false"><?= getMessage('APP_PANEL_AUTO_EXPORT_FROM_B24') ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" id="import-to-b24-tab" data-toggle="tab" href="#import_to_b24" role="tab"
           aria-controls="import_to_b24"
           aria-selected="false"><?= getMessage('APP_PANEL_IMPORT_TO_B24') ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="feedback-data" data-toggle="tab" href="#feedback_data" role="tab"
           aria-controls="feedback_data"
           aria-selected="false"><?= getMessage('APP_PANEL_FEEDBACK') ?></a>
    </li>
</ul>

<div id="preloader" style="display: none">
    <div id="loader"></div>
</div>

<div id="errors"></div>
<div id="success"></div>

<div class="tab-content">
	<?
	//error_reporting(E_ALL);
	error_reporting(0);

	include_once "connection.php";
	include_once "export_entity_from_b24.php";
	include_once "auto_export_entity_from_b24.php";
	include_once "import_entity_to_b24.php";
	include_once "feedback.php";
	?>
</div>

<script src="../templates/js/common.js"></script>
<script>
    let select2Common = {
        closeOnSelect: false,
        placeholder: "<?= getMessage('SELECT_FIELDS') ?>",
        language: {
            noResults: function () {
                return "<?= getMessage('NO_RESULTS_FOUND') ?>";
            }
        }
    };
</script>
<script>
    BX24.init(function () {
        initSettings();
        initEventHandlers();

        //Get settings from b24 and show in GUI
        function initSettings() {
            //Get settings and set to gui
            let defaultSettigs = {};

            getOptions(function (result) {
                let appSettings = result.answer.result;
                showConnectionData(appSettings);
            });
        }

        //Init eventhandlers
        function initEventHandlers() {
            //Save connection settings
            $("#connectionForm").on('submit', function (event) {
                event.preventDefault();
                saveConnectionData();
            });

            // clearConnectionForm
            $("#clearConnectionForm").on('click', function (event) {
                event.preventDefault();
                clearConnectionData();
            });

            // data with Export From B24 to SendPulse
            $("#export-from-b24-tab").on('click', function (event) {
                event.preventDefault();
                showDataForExportFromB24Tab();
            });

            $("#auto-export-from-b24-tab").on('click', function (event) {
                event.preventDefault();
                showDataForAutoExportFromB24Tab();

            });

            $("#autoExportContactsFromB24Form").on('submit', function (event) {
                event.preventDefault();
                saveAutoExportContactsData();
            });

            $("#autoExportCompaniesFromB24Form").on('submit', function (event) {
                event.preventDefault();
                saveAutoExportCompaniesData();
            });

            // data with Import From SendPulse to B24
            $("#import-to-b24-tab").on('click', function (event) {
                event.preventDefault();
                showDataForImportToB24Tab();
            });

            // export entity from B24 to SendPulse (manually)
            $("#exportEntityFromB24Form").on('submit', function (event) {
                event.preventDefault();
                exportEntityFromB24();
            });

            // import entity from SendPulse to B24(manually)
            $("#importEntityToB24Form").on('submit', function (event) {
                event.preventDefault();
                importEntityToB24();
            });

            // change entity in ExportEntityFromB24
            $("#exportFromB24EntitySelect").change(function (event) {
                event.preventDefault();
                changeEntityB24InExportEntityFromB24();
            });

            // change entity in ImportEntityToB24
            $("#importToB24EntitySelect").change(function (event) {
                event.preventDefault();
                changeEntityB24InImportEntityToB24();
            });
        }

        //Wrappers for API calls
        function saveOptions(settings, callback) {
            if (!callback) callback = function () {
            };
            BX24.callMethod('app.option.set', {options: settings}, callback);
        }

        function getOptions(callback) {
            if (!callback) callback = function () {
            };
            return BX24.callMethod('app.option.get', [], callback);
        }

        function saveConnectionData() {
            request('/ajax/sendpulse/connection.php', 'POST')
                .then(response => {
                    response = JSON.parse(response);

                    let errorsExist = showErrors(response);

                    if (!errorsExist) {
                        //save settings
                        saveOptions({
                            SEND_PULSE_ID: $('#SendPulseId').val(),
                            SEND_PULSE_SECRET: $('#SendPulseSecret').val()
                        }, function (res) {
                            $("#clearConnectionForm").prop('disabled', false);
                            $('.nav-link').removeClass('disabled');

                            alert("<?=getMessage('APP_CONNECTION_SAVED')?>");
                            location.reload();
                        });
                    } else {
                        $('.nav-link').addClass('disabled');
                        $('#connection-tab').removeClass('disabled');
                        $('#feedback-data').removeClass('disabled');
                    }
                });
        }

        function showConnectionData(appSettings) {

            $('#SendPulseId').val(appSettings.SEND_PULSE_ID);
            $('#SendPulseSecret').val(appSettings.SEND_PULSE_SECRET);

            if ($('#SendPulseId').val().length > 0 && $('#SendPulseSecret').val().length > 0) {
                $("#clearConnectionForm").prop('disabled', false);
                $('.nav-link').removeClass('disabled');
            } else {
                $("#clearConnectionForm").prop('disabled', true);
                $('.nav-link').addClass('disabled');
                $('#connection-tab').removeClass('disabled');
                $('#feedback-data').removeClass('disabled');
            }
        }

        function clearConnectionData() {
            removeErrors();

            $('#SendPulseId').val("");
            $('#SendPulseSecret').val("");

            $("#clearConnectionForm").prop('disabled', true);
            $('.nav-link').addClass('disabled');
            $('#connection-tab').removeClass('disabled');
            $('#feedback-data').removeClass('disabled');


            saveOptions({SEND_PULSE_ID: '', SEND_PULSE_SECRET: ''}, function (res) {
                alert("<?=getMessage('CONNECTION_DATA_REMOVED')?>");
                location.reload();
            });
        }

        function saveAutoExportContactsData() {
            let sendpulseBookID = parseInt($("#autoExportContactsToSendPulseBookSelect").val());

            if (!sendpulseBookID)
            {
                let info = {
                    errors: "<?=getMessage("NOT_SELECTED_SEND_PULSE_BOOK")?>"
                };
                showErrors(info, false);
                return;
            }
            removeErrors();

            saveOptions({
                AUTO_EXPORT_CONTACTS: $("#autoExportContacts").is(':checked'),
                AUTO_EXPORT_CONTACTS_SENDPULSE_BOOK: sendpulseBookID,
                AUTO_EXPORT_CONTACTS_FIELDS: $("#autoExportContactsFromB24FieldsSelect").val(),
            }, function (res) {
                alert("<?=getMessage('APP_CONNECTION_SAVED')?>");
            });
        }

        function saveAutoExportCompaniesData() {
            let sendpulseBookID = parseInt($("#autoExportCompaniesToSendPulseBookSelect").val());

            if (!sendpulseBookID)
            {
                let info = {
                    errors: "<?=getMessage("NOT_SELECTED_SEND_PULSE_BOOK")?>"
                };
                showErrors(info, false);
                return;
            }
            removeErrors();

            saveOptions({
                AUTO_EXPORT_COMPANIES: $("#autoExportCompanies").is(':checked'),
                AUTO_EXPORT_COMPANIES_SENDPULSE_BOOK: sendpulseBookID,
                AUTO_EXPORT_COMPANIES_FIELDS: $("#autoExportCompaniesFromB24FieldsSelect").val(),
            }, function (res) {
                alert("<?=getMessage('APP_CONNECTION_SAVED')?>");
            });
        }

        function showAutoExportContactsData(appSettings) {

            if (appSettings.AUTO_EXPORT_CONTACTS === 'true') {
                $("#autoExportContacts").prop("checked", true);
            }

            $('#autoExportContactsToSendPulseBookSelect option[value="' + appSettings.AUTO_EXPORT_CONTACTS_SENDPULSE_BOOK + '"]').prop('selected', true);

            if (appSettings.AUTO_EXPORT_CONTACTS_FIELDS)
            {
                $('#autoExportContactsFromB24FieldsSelect').select2('val', [appSettings.AUTO_EXPORT_CONTACTS_FIELDS]);
            }
        }

        function showAutoExportCompaniesData(appSettings) {
            if (appSettings.AUTO_EXPORT_COMPANIES === 'true') {
                $("#autoExportCompanies").prop("checked", true);
            }

            $('#autoExportCompaniesToSendPulseBookSelect option[value="' + appSettings.AUTO_EXPORT_COMPANIES_SENDPULSE_BOOK + '"]').prop('selected', true);

            if (appSettings.AUTO_EXPORT_COMPANIES_FIELDS)
            {
                $('#autoExportCompaniesFromB24FieldsSelect').select2('val', [appSettings.AUTO_EXPORT_COMPANIES_FIELDS]);
            }
        }

        function showDataForAutoExportFromB24Tab() {
            request('/ajax/sendpulse/get_address_books.php', 'POST')
                .then(response => {
                    response = JSON.parse(response);

                    let errorsExist = showErrors(response);

                    if (!errorsExist) {
                        $('.autoExportFromB24EntitySelect').empty();

                        $('.autoExportFromB24EntitySelect').append(`<option value='' selected disabled><?=getMessage("NOT_SELECTED")?></option>`);

                        response.forEach((element) => {
                            $('.autoExportFromB24EntitySelect').append(`<option value="${element.id}">${element.name}</option>`);
                        });

                        getOptions(function (result) {
                            let appSettings = result.answer.result;
                            showAutoExportContactsData(appSettings);
                            showAutoExportCompaniesData(appSettings);
                        });
                    }
                });
        }


    });

    function prohibitionRemovalRequiredFields() {
        $('.select2-selection__choice:contains("EMAIL")').find('.select2-selection__choice__remove').remove();
    }

    function request(url, httpMethod = "POST", inputData = {}) {
        let initData = {
            id: $('#SendPulseId').val(),
            secret: $('#SendPulseSecret').val(),
            DOMAIN: "<?=$_REQUEST['DOMAIN']?>",
            member_id: "<?=$_REQUEST['member_id']?>",
            lang: "<?=$_REQUEST['LANG']?>",
        };

        let data = Object.assign(initData, inputData);

        if (!(data instanceof FormData)) {
            let formData = new FormData();
            data = objToForm(data, formData)
        }

        let init = {
            type: httpMethod,
            url: url,
            data: data,
            contentType: false,
            processData: false,
            beforeSend: function () {
                showPreloader();
            },
            success: function () {
                hidePreloader();
            },
            error: function () {
                hidePreloader();
            }
        };

        return $.ajax(init);
    }
</script>