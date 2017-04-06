<?php
/**
 * Template functions
 *
 * @package phreak
 * @author Simeon Lyubenov <lyubenov@gmail.com>
 * @link http://www.lamez.org
 * @link https://www.webdevlabs.com
 */

namespace System;

class Template extends \Smarty {
	private $conf;

	public function __construct (Config $conf, Language $language) {
		parent::__construct();
		$this->conf = $conf;

		$this->setCompileCheck(true); // set true to require smarty check if the template file is modified
		$this->force_compile = false; // set true only for debugging purposes

		$this->assign('requestURI',$_SESSION['requestURI']);
		$this->assign('language',$language->current);
		if ($language->default !== $language->current) {
			$baseurl = BASE_URL.'/'.$language->current;
		}else {
			$baseurl = BASE_URL;
		}		
		$this->assign('baseurl',$baseurl);

		$this->setTemplateDir($this->conf->template['template_dir'])
		->setCompileDir($this->conf->template['compile_dir'])
		->setCacheDir($this->conf->template['cache_dir'])
		->setConfigDir($this->conf->template['languages_dir'])
		->addPluginsDir($this->conf->template['plugins_dir']);

		// register basic internal functions
		$this->registerPlugin('function', "show_msg", array($this, 'show_msg'));
		$this->registerPlugin('function', "count", array($this, 'basic_count'));
		$this->registerPlugin('modifier', "roundmoney", array($this, 'roundmoney'));
//		$this->registerPlugin('modifier', "ago", 'ago');
		// if not logged in as admin
		if (!$_SESSION['admin_id'] > "0") {
			if ($this->conf->encode_output_emails == '1') {
//				$this->registerFilter("output", array($this, 'protect_email')); // encode email addresses
				$this->loadFilter('output', 'protect_email');
			}
		}

		// if on frontend
//		if (!$this->url->inAdmin) {
		if ('tova_ne_e_vadmin'!=='da') {
			if ($this->conf->combine_js) {
//				$this->registerFilter("output", array($this, 'combine_js')); // enable combine_js
				$this->loadFilter('output', 'combine_js');
			}
			if ($this->conf->combine_css) {
//				$this->registerFilter("output", array($this, 'combine_css')); // enable combine_css
				$this->loadFilter('output', 'combine_css');
			}
			// Cache settings
			if ($this->conf->cache['smarty']['lifetime'] > 0) {
				$this->setCacheLifetime($this->conf->cache['smarty']['lifetime']); // 1 hour
			} else {
				$this->setCacheLifetime(3600); // 1 hour
			}
			if ($this->conf->cache['smarty']['driver']=='files') {
				$this->setCaching(true);
				$this->setCompileCheck(false);
			}
			switch ($this->conf->caching_memory) {
				case "opcache":
					ini_set('opcache.use_cwd', true); // Enable to prevent collisions between files with the same base name.
					ini_set('opcache.validate_timestamps', true);
					ini_set('opcache.revalidate_freq', $this->conf->cache_lifetime);
					break;
				case "memcached":
					$this->setCachingType('memcache'); // Memcached Cache - /etc/default/memcached to enable sys daemon.
					break;
				case "apc":
					$this->setCachingType('apc'); // APC Cache
					break;
				default:
					ini_set('opcache.enable', 0);
			}

			if ($this->conf->minify_html_front == '1') {
				$this->loadFilter('output', 'trimwhitespace'); // enable smarty internal html minifier
			}
			//		$this->registerFilter("output",array($this,'async_css_load'));
		}

		// Set template variables
		$this->assign('conf',$this->conf);
		// Go through config/template.php "assign" section and assign template values 
		if (count($this->conf->template['assign'])) {
			foreach ($this->conf->template['assign'] as $tkey => $tval) {
				$this->assign($tkey, $tval);
			}
		}
	}

	/**
	 * Override Smarty's built-in 'display' function
	 *
	 * @param string $template filename
	 * @param string $cache_id
	 * @param string $compile_id
	 * @param string $parent
	 * @return mixed
	 */
	function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		if ($this->conf->template['nocache'][$template]) {
			parent::clearCache($template);
		}
		if (!$cache_id) {
			$cache_id=$_SERVER['REQUEST_URI'];
		}
		parent::display($template, $cache_id, $compile_id, $parent);
	}

	/**
	 * Set template notification message (FlashBag)
	 *
	 * @param string $message
	 * @return null
	 */
	function set_msg($message) {
		$_SESSION['msg'] = $message;
	}

	/**
	 * Show template notification message (FlashBag)
	 *
	 * @return null
	 */
	function show_msg() {
		$message = $_SESSION['msg'];
		unset($_SESSION['msg']);
		$this->assign('msg', $message);
	}

	// ---------- EOF CLASS.TEMPLATE.PHP
}
