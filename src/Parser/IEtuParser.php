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
	 * @param string $date
	 * @param Student $stud
	 * @param ImportedData|null $data
	 * @return string
	 */
	public function getReleveFileName(string $date, Student $stud, ImportedData $data = null): string;

	/**
	 * Return the name for an attestation.
	 * @param string $date
	 * @param Student $stud
	 * @param ImportedData|null $data
	 * @return string
	 */
	public function getAttestFileName(string $date, Student $stud, ImportedData $data = null): string;

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