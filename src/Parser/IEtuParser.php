<?php


namespace App\Parser;


use App\Entity\ImportedData;
use App\Entity\Student;

interface IEtuParser
{
	/**
	 * Parse .etu file into Student array
	 * @param string $filename
	 * @return array
	 */
	public function parseETU(string $filename): array;

	/**
	 * Return the number of duplicate student into the parsed .etu
	 * Used when we have pagination.
	 * @return int
	 */
	public function getNbDoublons(): int;

    /**
     * Return the name for a releve.
     * @param ImportedData $data
     * @param string $num
     * @return string
     */
	public function getReleveFileName(ImportedData $data, string $num): string;

    /**
     * Return the name for an attestation.
     * @param ImportedData $data
     * @param string $num
     * @return string
     */
    public function getAttestFileName(ImportedData $data, string $num): string;


	/**
	 * Retrieve a student from the pdf content by applying numero regexes.
	 * @param string $content
	 * @param array $students
	 * @return mixed
	 */
	public function findStudentByNum(string $content, array $students);

	/**
	 * Retrieve a student from the pdf content by applying name and birthday regexes.
	 * @param string $content
	 * @param array $students
	 * @return mixed
	 */
	public function findStudentByName(string $content, array $students);
}