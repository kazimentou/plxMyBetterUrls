<?php
if(!defined('PLX_ROOT')) { exit; }

if(!empty($_POST)) {
	# Control du token du formulaire
	plxToken::validateFormToken($_POST);

	if(!empty($_POST['ext_url']) and $_POST['ext_url'][0] != '.')
		plxMsg::Error($plxPlugin->getLang('L_BAD_URL_EXTENSION'));
	else {
		$plxPlugin->setParam('ext_url', !empty($_POST['ext_url']) ? '.' . plxUtils::title2url($_POST['ext_url']) : '', 'string');
		foreach(array('article', 'category', 'static') as $field) {
			$name = 'format_' . $field;
			$plxPlugin->setParam($name, plxUtils::title2url($_POST[$name]), 'string');
		}
		$plxPlugin->saveParams();
	}
	header('Location: parametres_plugin.php?p=' . $plugin);
	exit;
}

?>
<form id="form_config_plugin" method="post">
	<fieldset>
		<p class="field"><label for="id_ext_url"><?php $plxPlugin->lang('L_URLS_EXTENSION') ?>&nbsp;:</label></p>
		<input onkeyup="upd_spans(this.value)" type="text" id="id_ext_url" name="ext_url" size="10" maxlength="11" value="<?= $plxPlugin->getParam('ext_url') ?>" />&nbsp;ex: <strong>.html</strong>, .htm, .php, .asp

		<p><?php $plxPlugin->lang('L_URLS_FORMAT') ?></p>

		<p class="field">
<?php
$urls = array(
	'article'	=> L_DEFAULT_NEW_ARTICLE_URL,
	'category'	=> L_DEFAULT_NEW_CATEGORY_URL,
	'static'	=> L_DEFAULT_NEW_STATIC_URL,

);
foreach(array('article', 'category', 'static') as $field) {
	$name = 'format_' . $field;
?>
			<label for="id_format_<?= $field ?>"><?php $plxPlugin->lang('L_' . strtoupper($field)) ?> :</label>
			<?php echo $plxAdmin->aConf['racine'] ?><?php plxUtils::printInput($name, $plxPlugin->getParam($name),'text','5-255') ?>/<?= $urls[$field] ?><span class="ext_url"><?php echo $plxPlugin->getParam('ext_url') ?></span>
<?php
}
?>
		</p>

		<p class="in-action-bar">
			<?php echo plxToken::getTokenPostMethod() ?>
			<input type="submit" value="<?php $plxPlugin->lang('L_SAVE') ?>" />
		</p>
	</fieldset>
</form>
<script>
(function() {
	const spans = Array.from(document.querySelectorAll('#form_config_plugin .ext_url'));
	const input = document.forms[0].elements['ext_url'];
	input.onkeyup = function(event) {
		spans.forEach(function(el) {
			el.textContent = input.value;
		})
	};
})();
</script>
