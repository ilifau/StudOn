<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * fim: [pdf] new Class ilPhantomJsPdfGenerator
 * 
 * @author  Fred Neumann fred.neumann@fau.de>
 * @version $Id$
 */
class ilPhantomJsPdfGenerator
{
	/**
	 * @see ilTestPDFGenerator
	 */
	const PDF_OUTPUT_DOWNLOAD = 'D';
	const PDF_OUTPUT_INLINE = 'I';
	const PDF_OUTPUT_FILE = 'F';

	public static function generatePDF(ilPDFGenerationJob $job)
	{
		global $ilCust;

		$html = "";
		foreach ($job->getPages() as $page)
		{
			$html .= $page;
		}

		// debugging output
		// echo $html;
		// exit;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,            $ilCust->getSetting('pdf_server'));
		curl_setopt($ch, CURLOPT_TIMEOUT, 		 $ilCust->getSetting('pdf_timeout'));		// seconds
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true );
		curl_setopt($ch, CURLOPT_POST,           true );
		curl_setopt($ch, CURLOPT_POSTFIELDS,     $html );
		curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/html; charset=utf-8'));

		$pdf = curl_exec ($ch);

		if ($pdf)
		{
			curl_close($ch);
			$pdf = base64_decode($pdf);

			switch ($job->getOutputMode())
			{
				case self::PDF_OUTPUT_FILE:
					file_put_contents($job->getFilename(), $pdf);
					break;

				case self::PDF_OUTPUT_DOWNLOAD:
				case self::PDF_OUTPUT_INLINE:
				default:
					ilUtil::deliverData($pdf, $job->getFilename());
					break;
			}
		}
		else
		{
			ilUtil::sendFailure(curl_error($ch), true);
			curl_close($ch);
		}
	}
}