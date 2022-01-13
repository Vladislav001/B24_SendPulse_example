<div class="tab-pane fade show active" id="connection" role="tabpanel" aria-labelledby="connection-tab">
    <br>
    <div class="container-fluid">
        <div class="h6">
			<?= getMessage('APP_CONNECTION_INSTRUCTION') ?>
            <a href="https://login.sendpulse.com/settings/#api" target="_blank">
                https://login.sendpulse.com/settings/#api
            </a>
        </div>
        <br>
        <form id="connectionForm">
            <div class="form-group row">
                <label for="id" class="col-sm-2 col-form-label">ID:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="SendPulseId" autocomplete="off">
                </div>
            </div>
            <div class="form-group row">
                <label for="secret" class="col-sm-2 col-form-label">Secret:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="SendPulseSecret" autocomplete="off">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-10">
                    <button type="submit" class="btn btn-primary"><?= getMessage('APP_CONNECTION_SAVE') ?></button>
                </div>
            </div>
        </form>
        <div class="form-group row">
            <div style="display:flex; justify-content:flex-end; width:100%; padding:0;">
                <button type="button" class="btn btn-danger"
                        id="clearConnectionForm"><?= getMessage('CONNECTION_DATA_REMOVE') ?></button>
            </div>
        </div>

    </div>

</div>