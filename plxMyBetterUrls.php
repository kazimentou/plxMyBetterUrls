<?php
/**
 * Plugin plxMyBetterUrls
 *
 * @author	Stephane F, Jean-Pierre Pourrez "bazooka07"
 * Updated on april, the 14th of 2021
 **/
class plxMyBetterUrls extends plxPlugin {

	const BEGIN_CODE = '<?php /* ' . __CLASS__ . ' plugin */' . PHP_EOL;
	const END_CODE = PHP_EOL . '?>';
	const REDIRECTION = '301'; # redirection permanente
	# const REDIRECTION = '302'; # redirection temporaire

	/**
	 * Constructeur de la classe
	 *
	 * @param	default_lang	langue par défaut
	 * @return	stdio
	 * @author	Stephane F
	 **/
	public function __construct($default_lang) {

		# appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);

		# droits pour accéder à la page config.php du plugin
		$this->setConfigProfil(PROFIL_ADMIN);

		# initialisation des variables de la classe
		$this->article = trim($this->getParam('format_article'), '/');
		$this->category = trim($this->getParam('format_category'), '/');
		$this->static = trim($this->getParam('format_static'), '/');
		$this->ext_url = $this->getParam('ext_url');
		if(!empty($this->article)) { $this->article .= '/'; }
		if(!empty($this->category)) { $this->category .= '/'; }
		if(!empty($this->static)) { $this->static .= '/'; }

		# déclaration des hooks
		$this->addHook('plxMotorConstructLoadPlugins', 'plxMotorConstructLoadPlugins');
		$this->addHook('plxMotorConstruct', 'plxMotorConstruct');
		$this->addHook('plxMotorDemarrageNewCommentaire', 'plxMotorDemarrageNewCommentaire');
		$this->addHook('plxFeedPreChauffageBegin', 'plxFeedPreChauffageBegin');
		foreach(array(
			'IndexEnd',
			'FeedEnd',
			'SitemapEnd',
		) as $hook) {
			$this->addHook($hook, 'rewriteUrls');
		}
	}

	/**
	 * Méthode qui fait une redirection si accès à PluXml à partir des urls standards de PluXml
	 * redirection 301 ou 302 selon valeur de self::REDIRECTION
	 * 1er hook disponible dans plxMotor::_construct()
	 **/
	public function plxMotorConstructLoadPlugins() {
		echo self::BEGIN_CODE;
?>
if(
	empty($this->get) or
	preg_match('#/index\.php\?preview$#', $_SERVER['REQUEST_URI']) or
	(
		isset($_SERVER['HTTP_REFERER']) and
		preg_match('#/core/admin/\w+\.php#', $_SERVER['HTTP_REFERER'])
	) or
	defined('PLX_ADMIN')
) {
	# Page d'accueil ou Preview article ou static page ou 1ère connexion
	return;
}

if(substr(str_replace($_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']),-1) == '?') {
	# redirection si lien http://server.com/?contenu vers http://server.com/contenu
	header('Status: <?= self::REDIRECTION ?> Moved Permanently', false, <?= self::REDIRECTION ?>);
	header('Location: ' . $this->urlRewrite($_SERVER['QUERY_STRING']));
	exit();
}

if(preg_match('#^(article|static|categorie)\d{3,4}/([\w-]+)(/page\d+)?#', $this->get, $capture)) {
	if($capture[1] != '<?= $this->article ?>') {
		$page = isset($capture[3]) ? $capture[3] : '';
		header('Status: <?= self::REDIRECTION ?> Moved Permanently', false, <?= self::REDIRECTION ?>);
		header('Location: ' . $this->urlRewrite($capture[2] . '<?= $this->ext_url ?>' . $page));
		exit();
	}
}

if(preg_match('#index.php\?((?:tag|archives)/.*)$#', $_SERVER['REQUEST_URI'], $captures)) {
	header('Status: <?= self::REDIRECTION ?> Moved Permanently', false, <?= self::REDIRECTION ?>);
	header('Location: ' . $this->urlRewrite($captures[1]));
	exit();
}
<?php
		echo self::END_CODE;
	}

	/**
	 * Méthode qui rédirige vers la bonne url après soumission d'un commentaire
	 *
	 * @author	Stephane F
	 **/
	public function plxMotorDemarrageNewCommentaire() {
		echo self::BEGIN_CODE;
?>
$url = $this->urlRewrite('?<?= $this->lang . $this->article ?>' . $this->plxRecord_arts->f('url') . '<?= $this->ext_url ?>');
<?php
		echo self::END_CODE;
	}

	/**
	 * Méthode qui recrée l'url de l'article, page statique ou catégorie au format natif de PluXml
	 *
	 * @author	Stephane F
	 **/
	public function plxMotorConstruct() {

		# récupération de la langue si plugin plxMyMultilingue présent
		$this->lang = '';
		if(class_exists('plxMyMultiLingue')) {
			$lang = plxMyMultiLingue::_Lang();
			if(
				!empty($lang) and
				isset($_SESSION['default_lang']) AND
				$_SESSION['default_lang'] != $lang
			) {
				$this->lang = $lang . '/';
			}
		}

		echo self::BEGIN_CODE;
?>
if(empty($this->get)) {
	return;
}

# récupération url
$get = $_SERVER["QUERY_STRING"];

# récupération de la pagination si présente
$page = '';
if(preg_match('#page\d+$#', $this->get, $capture)) {
	$page = '/' . $capture[0];
}

# suppression de la page dans url
$get = str_replace($page, '', $get);

# pages statiques
foreach($this->aStats as $numstat => $stat) {
	if($get == $stat['url']) {
		$get = '<?= $this->lang . $this->static ?>' . $get;
	}

	$link = '<?= $this->lang . $this->static ?>' . $stat['url'] . '<?= $this->ext_url ?>';
	if($link == $get) {
		$this->get = '<?= $this->lang ?>static' . intval($numstat) . '/' . $stat['url'];
		return;
	}
}

# categories
foreach($this->aCats as $numcat => $cat) {
	$link = '<?= $this->lang . $this->category ?>' . $cat['url'] . '<?= $this->ext_url ?>';
	if($link == $get) {
		$this->get = '<?= $this->lang ?>categorie' . intval($numcat) . '/' . $cat['url'] . $page;
		return;
	}
}

# articles
foreach($this->plxGlob_arts->aFiles as $numart => $filename) {
	if(preg_match('#^\d{4}\.([\d,|home|draft]*)\.\d{3}\.\d{12}\.([\w-]+)\.xml$#', $filename,$captures)) {
		$link = '<?= $this->lang . $this->article ?>' . $captures[2] . '<?= $this->ext_url ?>';
		if($link == $get) {
			$this->get = '<?= $this->lang ?>article' . intval($numart) . '/' . $captures[2];
			return;
		}
	}
}

<?php
		echo self::END_CODE;
	}

	/**
	 * Méthode qui nettoie les urls des articles, pages statiques et catégories
	 *
	 * @author	Stephane F
	 **/
	public function rewriteUrls() {
		echo self::BEGIN_CODE;
?>
$replaces = array(
	'article'	=> '<?= $this->article ?>',
	'categorie'	=> '<?= $this->category ?>',
	'static'	=> '<?= $this->static ?>',
);
$output = preg_replace_callback(
	'#\b(article|categorie|static)\d{1,4}/([\w-]+)#',
	function($matches) use($replaces) {
		return $replaces[$matches[1]] . $matches[2] . '<?= $this->ext_url ?>';
	},
	$output
);
<?php
		echo self::END_CODE;
	}

	public function plxFeedPreChauffageBegin() {
		# flux rss des articles d'une categorie
		echo self::BEGIN_CODE;
?>
if(preg_match('#^rss/<?= $this->category ?>([\w-]+)#', $this->get, $captures)) {
	foreach($this->aCats as $numcat => $cat) {
		if($cat['url'] == $captures[1]) {
			$this->get = 'rss/categorie' . intval($numcat);
			break;
		}
	}
}
<?php
		echo self::END_CODE;
	}
}
