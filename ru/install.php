<?
if (!defined('LANG_ROOT_PATH'))
{
	define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/ru');
}
?>

<script src="//api.bitrix24.com/api/v1/"></script>
<script>
    BX24.init(function () {
        BX24.callBind('onCrmContactAdd', 'https://<?=$_SERVER["SERVER_NAME"]?>/ru/handlers/onCrmContactAdd.php');
        BX24.callBind('onCrmCompanyAdd', 'https://<?=$_SERVER["SERVER_NAME"]?>/ru/handlers/onCrmCompanyAdd.php');
    });
</script>

<? require_once($_SERVER['DOCUMENT_ROOT'] . '/install_common.php');