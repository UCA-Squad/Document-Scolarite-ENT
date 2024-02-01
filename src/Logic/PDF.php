<?php

namespace App\Logic;


use App\Entity\ImportedData;
use App\Parser\EtuParser;
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

/**
 * Classe permettant d'effectuer des actions sur des documents PDF.
 */
class PDF
{
    protected $dateRegex = '/(A?a?nné?e+.*)?([0-9]{4})[\/-][0-9]{2}([0-9]{2})/';
    protected $dateRegex1 = '/([Aa]\s?n\s?n\s?é?\s?e+\s?.*)([0-9 ]{8,})[ \/-]+[0-9 ]{3,}([0-9 ]{3,})/';

    protected $dateRegex2 = '/à compter du [0-9]+ (.*) ([0-9]{4})/';

    protected $getFilename = ImportedData::RN;
    protected $env;

    private $image_position = null;
    private $image = null;

    private $file_access;
    private $parser;

    public function __construct(ParameterBagInterface $params, FileAccess $file_access, EtuParser $parser)
    {
        $this->env = $params->get('kernel.environment');
        $this->file_access = $file_access;
        $this->parser = $parser;
    }

    /**
     * Register the image used to display on each document.
     * Call it before truncate.
     * @param $image_path
     */
    public function setupImage($image_path)
    {
        $this->image = $image_path;
    }

    /**
     * Register the image's position to display on each document.
     * Call it before truncate.
     * @param float $x
     * @param float $y
     */
    public function setupPosition(float $x, float $y)
    {
        $this->image_position = ["x" => $x, "y" => $y];
    }

    public function setupRn()
    {
        $this->getFilename = ImportedData::RN;
    }

    public function setupAttest()
    {
        $this->getFilename = ImportedData::ATTEST;
    }

    private $months = array(
        'janvier' => 'january',
        'février' => 'february',
        'mars' => 'march',
        'avril' => 'april',
        'mai' => 'may',
        'juin' => 'june',
        'juillet' => 'july',
        'août' => 'august',
        'septembre' => 'september',
        'octobre' => 'october',
        'novembre' => 'november',
        'décembre' => 'december',
    );

    /**
     * Return an array mapping each pdf page to a student
     * @param string $filename
     * @param array $students
     * @return array|bool
     * @throws Exception
     */
    public function indexPages(string $filename, array $students)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filename);

        $date = "";
        $indexes = [];

        $i = 0;
        foreach ($pdf->getPages() as $page) {
            $i++;
            $content = $page->getText();
            $index = $this->parser->findStudentByNum($content, $students);
            if ($index === false)
                $index = $this->parser->findStudentByName($content, $students);

            // Date modif here - A verifier
            if ($date == "" && preg_match($this->dateRegex, $content, $ymatches)) {
                $index1 = count(($ymatches)) == 4 ? 2 : 1;
                $index2 = count(($ymatches)) == 4 ? 3 : 2;
                $date = $ymatches[$index1] . '-' . $ymatches[$index2];
            } else if ($date == "" && preg_match($this->dateRegex1, $content, $ymatches)) {
                $date = str_replace(' ', '', $ymatches[2]) . '-' . str_replace(' ', '', $ymatches[3]);
            } else if ($date == "" && preg_match($this->dateRegex2, $content, $ymatches)) {
                $month = date_parse($this->months[$ymatches[1]]);
                $year = $ymatches[2];
                if ($month['month'] <= 11)
                    $date = ($year - 1) . '-' . substr($year, 2);
                else
                    $date = $year . '-' . (substr($year, 2) + 1);
            }

            if ($index !== false)
                $indexes[$i]['num'] = $index;
            else if (!empty($indexes)) {
                if ($this->env == "dev") {
                    throw new Exception("Impossible d'extraire les identifiants de l'étudiant page $i :\n\n$content");
                }
                return false;
            }
        }

        if (!isset($date) || $date == "")
            throw new Exception("Impossible d'extraire l'année universitaire");

        if (count($students) + $this->parser->getNbDoublons() === count($indexes) ||
            count($students) + $this->getNbDoublonPagination($indexes) === count($indexes))
            return [$date, $indexes];

        if ($this->env == "dev") {
//			dump("Nb students : " . count($students));
//			dump("Nb Index : " . count($pageStudent['indexes']));
//			dump("Nb doublon : " . $etu_parser->getNbDoublons());
//			dump($pageStudent);
            throw new Exception("Nombre d'étudiants et de pages pdf incohérent");
        }

        return false;
    }

    private function getNbDoublonPagination(array $pageStudent): int
    {
        $nb = 0;
        $num = -1;
        foreach ($pageStudent as $index => $value) {
            if ($num == $pageStudent[$index]['num'])
                $nb++;
            else
                $num = $pageStudent[$index]['num'];
        }
        return $nb;
    }

    /**
     * @throws PdfParserException
     */
    public function getPageCount(string $filename): int
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filename);
        $pdf->Close();
        return $pageCount;
    }

    /**
     * Sépare les différentes pages d'un document PDF en se basant sur un tableau d'index.
     * @param string $filename Le document PDF a éclater
     * @param ImportedData $data
     * @param string $directory
     * @param array $index Le tableau d'index permettant la séparation des pages
     * @param array $etu
     * @param int $i
     * @param bool $setup
     * @return int
     * @throws CrossReferenceException
     * @throws FilterException
     * @throws PdfParserException
     * @throws PdfReaderException
     * @throws PdfTypeException
     */
    public function truncateFileByPage(string $filename, ImportedData $data, string $directory, array $index, array $etu, int $i, bool $setup = false): int
    {
        // Operations on big pdf file take more than 120s then disable 'max_execution_time'
        // ini_set("max_execution_time", 0);

        if (!is_dir($directory) && !$setup) {
            mkdir($directory, 0777, true);
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filename);

        if ($i > $pageCount)
            return 0;

        if (isset($index[$i]['num'])) {
            $ret = $index[$i]['num'];
            $stud = $etu[$ret];
            $newPdf = new Fpdi();

            if (!$setup) $outputDir = $directory . $stud->getNumero();
            else $outputDir = $this->file_access->getPdfTamponByMode($this->getFilename, 'd');

            if (!is_dir($outputDir)) {
                mkdir($outputDir);
            }
            $newPdf->setSourceFile($filename);
            // While the page owner is the same, stack the page on the same pdf document
            while (isset($index[$i]['num']) && $index[$i]['num'] === $ret) {
                $newPdf->addPage();
                $newPdf->useTemplate($newPdf->importPage($i));
                $i++;
            }

            if ($this->image_position != null && $this->image != null)
                $newPdf->Image($this->image, $this->image_position['x'], $this->image_position['y'], 0, 0);

            if ($this->getFilename == ImportedData::RN)
                $str = $outputDir . '/' . ($setup ? $this->file_access->getPdfTamponByMode($this->getFilename, 'f') : $this->parser->getReleveFileName($data->getYear(), $stud, $data));
            else
                $str = $outputDir . '/' . ($setup ? $this->file_access->getPdfTamponByMode($this->getFilename, 'f') : $this->parser->getAttestFileName($data->getYear(), $stud, $data));
            $newPdf->output($str, 'F');
            $newPdf->Close();
            return $i;
        }
        return 0;
    }

    /**
     * Sépare les différentes pages d'un document PDF en se basant sur un tableau d'index.
     * @param string $filename Le document PDF à éclater
     * @param ImportedData $data
     * @param string $directory
     * @param array $index Le tableau d'index permettant la séparation des pages
     * @param array $etu
     * @param bool $setup
     * @return int
     * @throws CrossReferenceException
     * @throws FilterException
     * @throws PdfParserException
     * @throws PdfReaderException
     * @throws PdfTypeException
     */
    public function truncateFile(string $filename, ImportedData $data, string $directory, array $index, array $etu, bool $setup = false): int
    {
        // Operations on big pdf file take more than 120s then disable 'max_execution_time'
        // ini_set("max_execution_time", 0);

        if (!is_dir($directory) && !$setup) {
            mkdir($directory, 0777, true);
        }

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filename);
        $i = 1;
        $pdfCount = 0;
        while ($i <= $pageCount) {
            if (isset($index[$i]['num'])) {
                $ret = $index[$i]['num'];
                $stud = $etu[$ret];
                $newPdf = new Fpdi();

                if (!$setup) $outputDir = $directory . $stud->getNumero();
                else $outputDir = $this->file_access->getPdfTamponByMode($this->getFilename, 'd');

                if (!is_dir($outputDir)) {
                    mkdir($outputDir);
                }
                $newPdf->setSourceFile($filename);
                // While the page owner is the same, stack the page on the same pdf document
                while (isset($index[$i]['num']) && $index[$i]['num'] === $ret) {
                    $newPdf->addPage();
                    $newPdf->useTemplate($newPdf->importPage($i));
                    $i++;
                }
                if ($this->image_position != null && $this->image != null)
                    $newPdf->Image($this->image, $this->image_position['x'], $this->image_position['y'], 0, 0);
                if ($this->getFilename == ImportedData::RN)
                    $str = $outputDir . '/' . ($setup ? $this->file_access->getPdfTamponByMode($this->getFilename, 'f') : $this->parser->getReleveFileName($data->getYear(), $stud, $data));
                else
                    $str = $outputDir . '/' . ($setup ? $this->file_access->getPdfTamponByMode($this->getFilename, 'f') : $this->parser->getAttestFileName($data->getYear(), $stud, $data));
                $newPdf->output($str, 'F');
                $newPdf->Close();
                $pdfCount++;
            } else
                $i++;
        }
        return $pdfCount;
    }

}
