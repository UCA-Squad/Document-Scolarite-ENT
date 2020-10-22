<?php

namespace App\Logic;


use App\Entity\ImportedData;
use App\Parser\IEtuParser;
use Exception;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Smalot\PdfParser\Parser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PDF
{
	protected $dateRegex = '/[Aa]nnée.*([0-9]{4})[\/-][0-9]{2}([0-9]{2})/';

	protected $getFilename = ImportedData::RN;

	protected $env;

	public function __construct(ParameterBagInterface $params)
	{
		$this->env = $params->get('kernel.environment');
	}

	public function setupRn()
	{
		$this->getFilename = ImportedData::RN;
	}

	public function setupAttest()
	{
		$this->getFilename = ImportedData::ATTEST;
	}

	/**
	 * Return an array mapping each pdf page to a student
	 * @param IEtuParser $etu_parser
	 * @param string $filename
	 * @param array $students
	 * @return array|bool
	 * @throws Exception
	 */
	public function indexPages(IEtuParser $etu_parser, string $filename, array $students)
	{
		$parser = new Parser();
		$pdf = $parser->parseFile($filename);

		$pageStudent = [
			'date' => "",
			'indexes' => []
		];

		$i = 0;
		foreach ($pdf->getPages() as $page) {
			$i++;
			$content = $page->getText();

			$index = $etu_parser->findStudentByNum($content, $students);
			if ($index === false)
				$index = $etu_parser->findStudentByName($content, $students);

			if ($pageStudent['date'] == "" && preg_match($this->dateRegex, $content, $ymatches))
				$pageStudent['date'] = $ymatches[1] . '-' . $ymatches[2];

			if ($index !== false)
				$pageStudent['indexes'][$i]['num'] = $index;
			else if (!empty($pageStudent['indexes'])) {
				if ($this->env == "dev")
					throw new Exception("IMPOSSIBLE DE RECUPERER LES INFORMATIONS POUR LE CONTENU - REGEX MISSIING :\n\n$content");
				return false;
			}
		}

		if (count($students) + $etu_parser->getNbDoublons() === count($pageStudent['indexes']))
			return $pageStudent;

		if ($this->env == "dev")
			throw new Exception("IMPOSSIBLE DE RECUPERER LES INFORMATIONS POUR LE CONTENU - REGEX MISSIING	 :\n\n$content");

		return false;
	}

	/**
	 * @param IEtuParser $parser
	 * @param string $filename
	 * @param ImportedData $data
	 * @param string $directory
	 * @param array $index
	 * @param array $etu
	 * @return int
	 * @throws CrossReferenceException
	 * @throws FilterException
	 * @throws PdfParserException
	 * @throws PdfReaderException
	 * @throws PdfTypeException
	 */
	public function truncateFile(IEtuParser $parser, string $filename, ImportedData $data, string $directory, array $index, array $etu): int
	{
		// Operations on big pdf file take more than 120s then disable 'max_execution_time'
		// ini_set("max_execution_time", 0);

		if (!is_dir($directory)) {
			mkdir($directory, 0777, true);
		}

		$pdf = new Fpdi();
		$pageCount = $pdf->setSourceFile($filename);
		$i = 1;
		$pdfCount = 0;
		while ($i <= $pageCount) {
			if (isset($index['indexes'][$i]['num'])) {
				$ret = $index['indexes'][$i]['num'];
				$stud = $etu[$ret];
				$newPdf = new Fpdi();
				$outputDir = $directory . $stud->getNumero();
				if (!is_dir($outputDir)) {
					mkdir($outputDir);
				}
				$newPdf->setSourceFile($filename);
				// While the page owner is the same, stack the page on the same pdf document
				while (isset($index['indexes'][$i]['num']) && $index['indexes'][$i]['num'] === $ret) {
					$newPdf->addPage();
					$newPdf->useTemplate($newPdf->importPage($i));
					$i++;
				}
				if ($this->getFilename == ImportedData::RN)
					$str = $outputDir . '/' . $parser->getReleveFileName($index['date'], $stud, $data);
				else
					$str = $outputDir . '/' . $parser->getAttestFileName($index['date'], $stud, $data);
				$newPdf->output($str, 'F');
				$newPdf->Close();
				$pdfCount++;
			} else
				$i++;
		}
		return $pdfCount;
	}

}
