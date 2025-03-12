<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $userRepository;
    private string $path = '/user/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->manager->getRepository(User::class);

        foreach ($this->userRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'user[email]' => 'Testing',
            'user[roles]' => 'Testing',
            'user[password]' => 'Testing',
            'user[username]' => 'Testing',
            'user[firstname]' => 'Testing',
            'user[lastname]' => 'Testing',
            'user[image]' => 'Testing',
            'user[is_verified]' => 'Testing',
            'user[is_terms]' => 'Testing',
            'user[is_gpdr]' => 'Testing',
            'user[created_at]' => 'Testing',
            'user[updated_at]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->userRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new User();
        $fixture->setEmail('My Title');
        $fixture->setRoles('My Title');
        $fixture->setPassword('My Title');
        $fixture->setUsername('My Title');
        $fixture->setFirstname('My Title');
        $fixture->setLastname('My Title');
        $fixture->setImage('My Title');
        $fixture->setIs_verified('My Title');
        $fixture->setIs_terms('My Title');
        $fixture->setIs_gpdr('My Title');
        $fixture->setCreated_at('My Title');
        $fixture->setUpdated_at('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('User');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new User();
        $fixture->setEmail('Value');
        $fixture->setRoles('Value');
        $fixture->setPassword('Value');
        $fixture->setUsername('Value');
        $fixture->setFirstname('Value');
        $fixture->setLastname('Value');
        $fixture->setImage('Value');
        $fixture->setIs_verified('Value');
        $fixture->setIs_terms('Value');
        $fixture->setIs_gpdr('Value');
        $fixture->setCreated_at('Value');
        $fixture->setUpdated_at('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'user[email]' => 'Something New',
            'user[roles]' => 'Something New',
            'user[password]' => 'Something New',
            'user[username]' => 'Something New',
            'user[firstname]' => 'Something New',
            'user[lastname]' => 'Something New',
            'user[image]' => 'Something New',
            'user[is_verified]' => 'Something New',
            'user[is_terms]' => 'Something New',
            'user[is_gpdr]' => 'Something New',
            'user[created_at]' => 'Something New',
            'user[updated_at]' => 'Something New',
        ]);

        self::assertResponseRedirects('/user/');

        $fixture = $this->userRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getEmail());
        self::assertSame('Something New', $fixture[0]->getRoles());
        self::assertSame('Something New', $fixture[0]->getPassword());
        self::assertSame('Something New', $fixture[0]->getUsername());
        self::assertSame('Something New', $fixture[0]->getFirstname());
        self::assertSame('Something New', $fixture[0]->getLastname());
        self::assertSame('Something New', $fixture[0]->getImage());
        self::assertSame('Something New', $fixture[0]->getIs_verified());
        self::assertSame('Something New', $fixture[0]->getIs_terms());
        self::assertSame('Something New', $fixture[0]->getIs_gpdr());
        self::assertSame('Something New', $fixture[0]->getCreated_at());
        self::assertSame('Something New', $fixture[0]->getUpdated_at());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new User();
        $fixture->setEmail('Value');
        $fixture->setRoles('Value');
        $fixture->setPassword('Value');
        $fixture->setUsername('Value');
        $fixture->setFirstname('Value');
        $fixture->setLastname('Value');
        $fixture->setImage('Value');
        $fixture->setIs_verified('Value');
        $fixture->setIs_terms('Value');
        $fixture->setIs_gpdr('Value');
        $fixture->setCreated_at('Value');
        $fixture->setUpdated_at('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/user/');
        self::assertSame(0, $this->userRepository->count([]));
    }
}
