<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\ImportedData;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ImportRNTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        // Authentifier le client pour bypasser CAS
        $this->authenticateClient('hutaille', 'main');

        // Charger la configuration depuis la variable d'environnement IMPORT_PAIRS
        $raw = getenv('IMPORT_PAIRS') ?: ($_ENV['IMPORT_PAIRS'] ?? '[]');
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException('La variable IMPORT_PAIRS doit contenir un JSON de paires pdf/etu.');
        }
        $this->pairs = $parsed;
    }

    private array $pairs = [
        // ['pdf' => '/tmp/doc-test/batedirn_39279.pdf', 'etu' => '/tmp/doc-test/batedirn_39279.etu'],
        // ['pdf' => '/tmp/doc-test/batedirn_52340.pdf', 'etu' => '/tmp/doc-test/batedirn_52340.etu'],
//        ['pdf' => '/tmp/doc-test/batedirn_100904.pdf', 'etu' => '/tmp/doc-test/batedirn_100904.etu'],
    ];

    private function makeUploadedFile(string $path, string $mime = null): UploadedFile
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('Fichier de test introuvable ou non lisible : %s', $path));
        }

        return new UploadedFile(
            $path,
            basename($path),
            $mime,
            null,
            false // test mode : bypass is_uploaded_file
        );
    }

    private function authenticateClient(string $username, string $firewall): void
    {
        $container = static::getContainer();

        // Crée une session de test via `session.factory` (le service `session` peut être absent)
        $session = $container->get('session.factory')->createSession();
        $session->start();

        // Utilise la classe User fournie par Symfony pour un utilisateur de test
        $user = new User($username, ['ROLE_ADMIN'], 'hugo.taillefumier@uca.fr');

        $token = new UsernamePasswordToken($user, $firewall, $user->getRoles());
        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function testImportMultiplePdfEtuPairs(): void
    {
        foreach ($this->pairs as $i => $pair) {

            $targetPath = dirname($pair['etu']) . DIRECTORY_SEPARATOR . 'X' . basename($pair['etu']);
            copy($pair['etu'], $targetPath);

            // Préparer les UploadedFile
            $pdfFile = $this->makeUploadedFile($pair['pdf'], 'application/pdf');
            $etuFile = $this->makeUploadedFile($targetPath, 'text/plain');

            ['pageCount' => $pageCount, 'pageFirst' => $pageFirst] = $this->callImportRN($pdfFile, $etuFile);

            $pageCurrent = $this->callTruncateUnit(ImportedData::RN, $pageFirst);
            while ($pageCurrent > 0) {
                $pageCurrent = $this->callTruncateUnit(ImportedData::RN, $pageCurrent);
            }

        }
    }

    private function callImportRN(UploadedFile $pdfFile, UploadedFile $etuFile): array
    {
        // Paramètres attendus par la route
        $parameters = ['sem' => '1', 'sess' => '1', 'lib' => 'test', 'numTampon' => '1',];

        // Fichiers envoyés (BrowserKit : fichiers en 5e argument)
        $server = ['CONTENT_TYPE' => 'multipart/form-data'];

        $this->client->request('POST', '/api/import/rn', $parameters, ['pdf' => $pdfFile, 'etu' => $etuFile], $server);

        // Assertions minimales : 2xx et structure JSON attendue
        $this->assertResponseIsSuccessful("Import failed");
        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content, "Response is not JSON");

        $data = json_decode($content, true);
        $pageCount = $data['pageCount'];
        $pageFirst = $data['pageFirst'];
        $this->assertArrayHasKey('step', $data, "Missing 'step' in response");
        $this->assertArrayHasKey('mode', $data, "Missing 'mode' in response");
        $this->assertEquals(ImportedData::RN, $data['mode'], "Unexpected mode");

        return ['pageCount' => $pageCount, 'pageFirst' => $pageFirst];
    }

    private function callTruncateUnit(int $mode, int $page): int
    {
        $payload = ['mode' => $mode, 'page' => $page,];
        $server = ['CONTENT_TYPE' => 'application/json'];

        $this->client->request('POST', '/api/import/truncate_unit', [], [], $server, json_encode($payload));

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $int = filter_var($content, FILTER_VALIDATE_INT);

        $this->assertIsInt($int, '/api/import/truncate_unit doit retourner un int');
        return (int)$int;
    }
}