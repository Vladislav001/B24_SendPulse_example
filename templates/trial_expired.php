<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <style>
        .container {
            width: calc(100% - 40px);
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            background: #3b506e;
            background-size: contain;
            min-height: 100px;
        }

        .header__title {
            font-family: 'OpenSans', Arial, sans-serif;
            font-weight: 300;
            font-size: 30px;
            color: #FFF;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <h1 class="header__title"><?= getMessage('TRIAL_OR_PAID_PERIOD_HAS_ENDED')?></h1>
    </div>
</header>

<main class="main">
    <div class="container">
        <p><?= getMessage('TRIAL_ENJOYE_TEXT')?> <b><?= getMessage('TRIAL_OR_PAID_PERIOD_HAS_ENDED')?></b></p>
        <p><?= getMessage('TRIAL_WRITE_US')?><a href="mailto:vguryevb24info@gmail.com"> vguryevb24info@gmail.com</a></p>
       <?= getMessage('TRIAL_PRICES')?>
        <hr>
    </div>
</main>
<footer class="footer">
    <div class="container">
        <p><?= getMessage('TRIAL_CONTACT_EMAIL')?><a href="mailto:vguryevb24info@gmail.com">vguryevb24info@gmail.com</a></p>
    </div>
</footer>
</body>
</html>