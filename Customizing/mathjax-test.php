<?php
	/**
	 * fim: [tex] test script for server-side mathjax generation.
	*/
	chdir("..");
	define('ILIAS_MODULE','Customizing');

	require_once("Services/Init/classes/class.ilInitialisation.php");
	ilInitialisation::initILIAS();

	if (empty($_POST))
	{
		$_POST['tex'] = '\Large\varepsilon=\sum_{i=1}^{n-1}\frac1{\Delta x}\int_{x_i}^{x_{i+1}}\left\{\frac1{\Delta x}\big[(x_{i+1}-x)y_i^\ast+(x-x_i)y_{i+1}^\ast\big]-f(x)\right\}^2dx';
	}

	require_once ("Services/Utilities/classes/class.ilLatex.php");
	$html = "<p>Rendered TeX: ". ilLatex::getInstance()->renderTex($_POST['tex']) . "in a line.</p>";

	if ($_POST['pdf'])
	{
		require_once './Services/PDFGeneration/classes/class.ilPDFGeneration.php';

		$job = new ilPDFGenerationJob();
		$job->setAutoPageBreak(true)
			->setCreator('ILIAS')
			->setFilename('mathjax-test')
			->setMarginLeft('20')
			->setMarginRight('20')
			->setMarginTop('20')
			->setMarginBottom('20')
			->setOutputMode('D')
			->addPage($html);

		ilPDFGeneration::doJob($job);
	}
?>

<html>
<head>
	<title>Testing the MathJax Server</title>
</head>
</html>
<body>
	<p>Enter LaTeX code:</p>
	<form action="<?php echo  $_SERVER['PHP_SELF'] ?>" method="post">
	<p><textarea style="width:100%; height:10em;" name="tex"><?php echo $_POST["tex"];?></textarea>
	</p>
	<p>
		<input type = "submit" name="html" value="Render HTML">
		<input type = "submit" name="pdf" value="Render PDF">
	</p>
	</form>
	<?php echo $html; ?>
</body>
</html>