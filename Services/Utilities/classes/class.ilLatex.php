<?php
/**
 * fim: [tex] Generating Latex formulas.
 */
class ilLatex
{
	/**
	 * @var ilLatex		singleton instance
	 */
	protected static $_instance = null;

	/**
	 * @var string		URL of the MathJax server
	 */
	protected $server_url = '';

	/**
	 * @var float		connection timeout in seconds
	 */
	protected $timeout = 5;

	/**
	 * @var string		final output format: 'svg', 'png'
	 */
	protected $output = 'svg';

	/**
	 * @var bool		embed the output in the html code (instead of image with url)
	 */
	protected $embed = false;

	/**
	 * @var bool		cache already rendered images
	 */
	protected $cache = false;

	/**
	 * @var bool	Use cURL extension for the call
	 *				this is automatically set if the extension is loaded
	 * 				otherwise allow_url_fopen must be set in php.ini
	 */
	protected $use_curl = true;

	/**
	 * @var array	Default options for calling the MathJax server
	 */
	protected $default_options = array(
		"format" => "TeX",
		"math" => '',		// TeX code
		"svg" => true,
		"mml" => false,
		"png" => false,
		"speakText" => false,
		"speakRuleset" => "mathspeak",
		"speakStyle"=> "default",
		"ex"=> 6,
		"width"=> 1000000,
		"linebreaks"=> false,
	);


	/**
	 * Singleton: constructor
	 */
	protected function __construct()
	{
		global $ilCust;

		$this->server_url = (string) $ilCust->getSetting('tex_server');
		$this->timeout = (float) $ilCust->getSetting('tex_timeout');
		$this->output = (string) $ilCust->getSetting('tex_output');
		$this->embed = (bool) $ilCust->getSetting('tex_embed');
		$this->cache = (bool) $ilCust->getSetting('tex_cache');

		// set the connection method
		$this->use_curl = extension_loaded('cURL');
	}

	/**
	 * Singleton: prevent cloning
	 */
	private final function __clone() {}

	/**
	 * Singleton: get instance
	 * @return ilLatex|null
	 */
	public static function getInstance()
	{
		if (self::$_instance === NULL) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}


	/**
	 * Generate svg image from tex code
	 * @param string	tex code
	 */
	public function renderTex($tex)
	{
		$options = $this->default_options;
		$options['math'] = $tex;

		switch ($this->output)
		{
			case 'svg':
				$options['svg'] = true;
				$options['png'] = false;
				$suffix = ".svg";
				break;

			case 'png':
				$options['svg'] = false;
				$options['png'] = true;
				$suffix = ".png";
				break;
		}

		// search for a saved rendered image
		$hash = md5($tex);
		$file = ilUtil::getWebspaceDir() . '/temp/tex/' .substr($hash,0,4).'/'.substr($hash,4,4).'/'.$hash.$suffix;

		if (!$this->cache or !is_file($file))
		{
			if ($this->use_curl)
			{
				$curl = curl_init($this->server_url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($options));
				curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

				$response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);

				if ( $status != 200 )
				{
					$lines = explode("\n", $response);
					return "[TeX rendering failed: ". $lines[1]. " ". htmlspecialchars($tex) . "]";
				}
			}
			else
			{
				$context  = stream_context_create(
					array(
					'http' => array(
						'method'  => 'POST',
						'content' => json_encode($options),
						'header'=>  "Content-Type: application/json\r\n",
						'timeout' => $this->timeout,
						'ignore_errors' => true
					)
				));
				$response = @file_get_contents( $this->server_url, false, $context );
				if (empty($response))
				{
					return "[TeX rendering failed: ". htmlspecialchars($tex) . "]";
				}
			}

			// create the parent directories recursively
			@mkdir(dirname($file), 0777, true);

			// save a rendered image to the temp folder
			file_put_contents($file, $response);
		}

		// output the image tag
		switch ($this->output)
		{
			case 'svg':
				if ($this->embed)
				{
					return empty($response) ? file_get_contents($file) : $response;
				}
				else
				{
					$svgfile = simplexml_load_file($file);
					$width = $svgfile['width'];
					$height = $svgfile['height'];
					return '<img src="'.ILIAS_HTTP_PATH.'/'.$file.'" style="width:'.$width.'; height:'.$height.';" />';
				}
				break;

			case 'png':
				list($width, $height) = getimagesize($file);
				if ($this->embed)
				{
					return '<img src="data:image/png;base64,'
						.base64_encode(empty($response) ? file_get_contents($file) : $response)
						.'" style="width:'.$width.'; height:'.$height.';" />';
				}
				else
				{
					return '<img src="'.ILIAS_HTTP_PATH.'/'.$file.'" style="width:'.$width.'; height:'.$height.';" />';
				}
				break;
		}
	}
}