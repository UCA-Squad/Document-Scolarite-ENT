<?php


namespace App\Logic;


use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DocapostFast
{
	private $client;
	private $pem_file;
	private $pem_password;
	private $url;
	private $siren;
	private $circuitId;
	private $enable;
	private $proxy;

	/**
	 * DocapostFast constructor.
	 * @param HttpClientInterface $client
	 * @param array $parameters
	 */
	public function __construct(HttpClientInterface $client, array $parameters)
	{
		$this->client = $client;
		foreach ($parameters as $key => $val)
			$this->$key = $val;
	}

	/**
	 * @return mixed
	 */
	public function isEnable(): bool
	{
		return $this->enable;
	}

	/**
	 * @param string $document
	 * @param string $label
	 * @param string $comment
	 * @param string $emailDestinataire
	 * @return string
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function uploadDocument(string $document, string $label, string $comment = "", string $emailDestinataire = ""): string
	{
		if (mime_content_type($document) != "application/pdf")
			throw new \Exception("Wrong file format");

		$formFields = [
			'label' => base64_encode($label),
			'comment' => $comment,
			'emailDestinataire' => $emailDestinataire,
			'content' => DataPart::fromPath($document),
		];
		$formData = new FormDataPart($formFields);

		$response = $this->sendQuery("POST", "$this->siren/$this->circuitId/upload", [
			'headers' => $formData->getPreparedHeaders()->toArray(),
			"body" => $formData->bodyToIterable()]);
		return $response->getContent();
	}

	/**
	 * @param $id
	 * @return string
	 * @throws ClientExceptionInterface
	 * @throws RedirectionExceptionInterface
	 * @throws ServerExceptionInterface
	 * @throws TransportExceptionInterface
	 */
	public function downloadDocument($id): string
	{
		$response = $this->sendQuery("GET", "$id/download");
		return $response->getContent();
	}

	/**
	 * @param string $method
	 * @param string $uri
	 * @param array $parameters
	 * @return ResponseInterface
	 * @throws TransportExceptionInterface
	 */
	private function sendQuery(string $method, string $uri, array $parameters = []): ResponseInterface
	{
		return $this->client->request(
			$method,
			$this->url . $uri,
			array_merge($parameters,
				[
					"local_cert" => $this->pem_file,
					"local_pk" => $this->pem_file,
					"passphrase" => $this->pem_password,
					"proxy" => $this->proxy
				])
		);
	}

}